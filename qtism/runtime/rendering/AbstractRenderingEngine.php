<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 * @author Jérôme Bogaerts, <jerome@taotesting.com>
 * @license GPLv2
 * @package qtism
 * @subpackage
 *
 */

namespace qtism\runtime\rendering;

use qtism\common\utils\Uri;
use qtism\data\content\Flow;
use qtism\data\content\interactions\Choice;
use qtism\data\content\RubricBlock;
use qtism\data\ShowHide;
use qtism\data\content\FeedbackElement;
use qtism\runtime\common\State;
use qtism\data\ViewCollection;
use qtism\data\View;
use qtism\data\QtiComponent;
use \SplStack;
use \DOMDocument;

/**
 * The base class to be used by any rendering engines.
 * 
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 *
 */
abstract class AbstractRenderingEngine implements RenderingConfig {

    /**
     * An array used to 'tag' explored Component object.
     * 
     * @var array
     */
    private $explorationMarker;
    
    /**
     * The stack of Component object that still have to be explored.
     * 
     * @var SplStack
     */
    private $exploration;
    
    /**
     * The currently rendered component.
     * 
     * @var QtiComponent
     */
    private $exploredComponent = null;
    
    /**
     * The last rendering.
     * 
     * @var mixed
     */
    private $lastRendering = null;
    
    /**
     * The stack where rendered components in other
     * constitution are stored for a later use.
     *
     * @var SplStack
     */
    private $renderingStack;
    
    /**
     * The stack where encountered xml:base values
     * will be stored.
     * 
     * @var SplStack
     */
    private $xmlBaseStack;
    
    /**
     * An associative array where keys are QTI class names
     * and values are AbstractRenderer objects.
     *
     * @var array
     */
    private $renderers;
    
    /**
     * An array containing the QTI classes to be ignored
     * for rendering.
     *
     * @var array
     */
    private $ignoreClasses = array();
    
    /**
     * The Choice rendering policy.
     *
     * @var integer
     * @see RenderingConfig For information about rendering policies.
    */
    private $choiceShowHidePolicy = RenderingConfig::CONTEXT_STATIC;
    
    /**
     * The Feedback rendering policy.
     *
     * @var integer
     * @see RenderingConfig For information about rendering policies
     */
    private $feedbackShowHidePolicy = RenderingConfig::CONTEXT_STATIC;
    
    /**
     * The View rendering policy.
     *
     * @var integer
     * @see RenderingConfig For information about rendering policies
     */
    private $viewPolicy = RenderingConfig::CONTEXT_STATIC;
    
    /**
     * The QTI views to be used while rendering in CONTEXT_AWARE mode.
     *
     * @var ViewCollection
     */
    private $views;
    
    /**
     * The State object used in CONTEXT_AWARE rendering mode.
     *
     * @var State
     */
    private $state;
    
    /**
     * Create a new AbstractRenderingObject.
     * 
     */
    public function __construct() {
        $this->setXmlBaseStack(new SplStack());
        $this->setViews(new ViewCollection(array(View::AUTHOR, View::CANDIDATE, View::PROCTOR, View::SCORER, View::TEST_CONSTRUCTOR, View::TUTOR)));
        $this->setState(new State());
        
        $this->ignoreQtiClasses('responseDeclaration');
        $this->ignoreQtiClasses('outcomeDeclaration');
        $this->ignoreQtiClasses('templateDeclaration');
        $this->ignoreQtiClasses('responseProcessing');
        $this->ignoreQtiClasses('responseProcessing');
    }
    
    /**
     * Get the Stack of Component objects that to be still explored.
     * 
     * @return SplStack
     */
    protected function getExploration() {
        return $this->exploration;
    }
    
    /**
     * Set the Stack of Component objects that have to be still explored.
     * 
     * @param SplStack $exploration
     */
    protected function setExploration(SplStack $exploration) {
        $this->exploration = $exploration;
    }
    
    /**
     * Set the array used to 'tag' components in order to know
     * whether or not they are already explored.
     * 
     * @return array
     */
    protected function getExplorationMarker() {
        return $this->explorationMarker;
    }
    
    /**
     * Set the array used to 'tag' components in order to know whether
     * or not they are already explored.
     * 
     * @param array $explorationMarker
     */
    protected function setExplorationMarker(array $explorationMarker) {
        $this->explorationMarker = $explorationMarker;
    }
    
    /**
     * Get the currently explored component.
     * 
     * @return QtiComponent
     */
    protected function getExploredComponent() {
        return $this->exploredComponent;
    }
    
    /**
     * Set the currently explored Component object.
     * 
     * @param QtiComponent $component
     */
    protected function setExploredComponent(QtiComponent $component = null) {
        $this->exploredComponent = $component;
    }
    
    /**
     * Set the last rendering.
     * 
     * @param mixed $rendering
     */
    protected function setLastRendering($rendering) {
        $this->lastRendering = $rendering;
    }
    
    /**
     * Get the last rendering.
     * 
     * @return mixed
     */
    protected function getLastRendering() {
        return $this->lastRendering;
    }
    
    public function render(QtiComponent $component) {
        
        // Reset the engine to its initial state.
        $this->reset();
        
        // Put the root $component on the stack.
        if ($this->mustIgnoreComponent($component) === false) {
            $this->getExploration()->push($component);
        }
        
        while (count($this->getExploration()) > 0) {
            $this->setExploredComponent($this->getExploration()->pop());
            
            // Component is final or not?
            $final = $this->isFinal();
            
            // Component is explored or not?
            $explored = $this->isExplored();
            
            if ($final === false && $explored === false) {
                // Hierarchical node: 1st pass.
                $this->registerXmlBase();
                $this->markAsExplored($this->getExploredComponent());
                $this->getExploration()->push($this->getExploredComponent());
                
                foreach ($this->getNextExploration() as $toExplore) {
                    // Maybe the component must be ignored?
                    if ($this->mustIgnoreComponent($toExplore) === false) {
                        $this->getExploration()->push($toExplore);
                    }
                }
            }
            else if ($final === false && $explored === true) {
                // Hierarchical node: 2nd pass.
                $this->processNode($this->resolveXmlBase());
                
                if ($this->getExploredComponent() === $component) {
                    // End of the rendering.
                    break;
                }
            }
            else {
                // Leaf node.
                $this->registerXmlBase();
                $this->processNode($this->resolveXmlBase());
                
                if ($this->getExploredComponent() === $component) {
                    // End of the rendering (leaf node is actually a lone root).
                    break;
                }
            }
        }
        
        $finalRendering = $this->createFinalRendering();
        $this->reset();
        return $finalRendering;
    }
    
    /**
     * Whether or not the currently explored Component object
     * is a final leaf of the tree structured explored hierarchy.
     * 
     * @return boolean
     */
    protected function isFinal() {
        return count($this->getNextExploration()) === 0;
    }
    
    /**
     * Get the children components of the currently explored component
     * for future exploration.
     * 
     * @return QtiComponentCollection The children Component object of the currently explored Component object.
     */
    protected function getNextExploration() {
        return $this->getExploredComponent()->getComponents();
    }
    
    /**
     * Wether or not the currently explored component has been already explored.
     * 
     * @return boolean
     */
    protected function isExplored() {
        return in_array($this->getExploredComponent(), $this->getExplorationMarker(), true);
    }
    
    /**
     * 
     * @param QtiComponent $component
     */
    protected function markAsExplored(QtiComponent $component) {
        $marker = $this->getExplorationMarker();
        $marker[] = $component;
        $this->setExplorationMarker($marker);
    }
    
    /**
     * Create the final rendering as it must be rendered by the final
     * implementation.
     * 
     * @return mixed
     */
    abstract protected function createFinalRendering();
    
    /**
     * Process the current node.
     * 
     * @param string $base the value of xml:base for the node to be processed.
     */
    protected function processNode($base = '') {
        $renderer = $this->getRenderer($this->getExploredComponent());
        $rendering = $renderer->render($this->getExploredComponent(), $base);
        $this->setLastRendering($rendering);
    }
    
    /**
     * Whether or not a component must be ignored or not while rendering. The following cases
     * makes a component to be ignored:
     * 
     * * The ChoiceHideShow policy is set to CONTEXT_AWARE and the variable referenced by the Choice's templateIdentifier attribute does not match the expected value.
     * * The FeedbackHideShow policy is set to CONTEXT_AWARE and the variable referenced by the FeedbackElement's identifier attribute does not match the expected value.
     * * The class of the Component is in the list of QTI classes to be ignored.
     * 
     * @param QtiComponent $component A Component you want to know if it has to be ignored or not.
     * @return boolean
     */
    protected function mustIgnoreComponent(QtiComponent $component) {
        
        // In the list of QTI class names to be ignored?
        if (in_array($component->getQtiClassName(), $this->getIgnoreClasses()) === true) {
            return true;
        }
        // Context Aware + FeedbackElement OR Context Aware + Choice
        else if (($component instanceof FeedbackElement && $this->getFeedbackShowHidePolicy() === RenderingConfig::CONTEXT_AWARE) || ($component instanceof Choice && $component->hasTemplateIdentifier() === true && $this->getChoiceShowHidePolicy() === RenderingConfig::CONTEXT_AWARE)) {
            $matches = $this->identifierMatches($component);
            $showHide = $component->getShowHide();
            return ($showHide === ShowHide::SHOW) ? !$matches : $matches;
        }
        // Context Aware + RubricBlock
        else if ($this->getViewPolicy() === RenderingConfig::CONTEXT_AWARE && $component instanceof RubricBlock) {
            $renderingViews = $this->getViews();
            $rubricViews = $component->getViews();
            
            // If one of the rendering views matches a single view
            // in the rubricBlock's view, render!
            foreach ($renderingViews as $v) {
                if ($rubricViews->contains($v) === true) {
                    return false;
                }
            }
            
            return true;
        }
        else {
            return false;
        }
    }
    
    /**
     * Whether or not the 'outcomeIdentifier'/'templateIdentifier' set on a templateElement/feedbackElement/choice
     * matches its 'identifier' attribute.
     * 
     * @param QtiComponent $component A TemplateElement or FeedbackElement or Choice element.
     * @return boolean
     */
    protected function identifierMatches(QtiComponent $component) {
        $variableIdentifier = ($component instanceof FeedbackElement) ? $component->getOutcomeIdentifier() : $component->getTemplateIdentifier();
        $identifier = $component->getIdentifier();
        $showHide = $component->getShowHide();
        $state = $this->getState();
        
        return (($val = $state[$variableIdentifier]) !== null && $val === $identifier);
    }
    
    /**
     * Set the renderers array.
     * 
     * @param array $renderers
     */
    protected function setRenderers(array $renderers) {
        $this->renderers = $renderers;
    }
    
    /**
     * Get the renderers array.
     * 
     * @return array
     */
    protected function getRenderers() {
        return $this->renderers;
    }
    
    /**
     * Set the array containing the QTI class names
     * to be ignored for rendering.
     * 
     * @param array $ignoreClasses
     */
    protected function setIgnoreClasses(array $ignoreClasses) {
        $this->ignoreClasses = $ignoreClasses;
    }
    
    public function getIgnoreClasses() {
        return $this->ignoreClasses;
    }
    
    public function ignoreQtiClasses($classes) {
        if (is_string($classes) === true) {
            $classes = array($classes);
        }
        
        $ignoreClasses = $this->getIgnoreClasses();
        $ignoreClasses = array_unique(array_merge($ignoreClasses, $classes));
        
        $this->setIgnoreClasses($ignoreClasses);
    }
    
    /**
     * Register a $renderer object to a given $qtiClassName.
     * 
     * @param string $qtiClassName A QTI class name.
     * @param AbstractRenderer $renderer An AbstractRenderer object.
     */
    public function registerRenderer($qtiClassName, AbstractRenderer $renderer) {
        $renderer->setRenderingEngine($this);
        $renderers = $this->getRenderers();
        $renderers[$qtiClassName] = $renderer;
        $this->setRenderers($renderers);
    }
    
    /**
     * Get the AbstractRenderer implementation which is appropriate to render the given
     * QtiComponent $component.
     * 
     * @param QtiComponent $component A QtiComponent object you want to get the appropriate AbstractRenderer implementation.
     * @throws RenderingException If no implementation of AbstractRenderer is registered for $component.
     * @return AbstractRenderer The AbstractRenderer implementation to render $component.
     */
    public function getRenderer(QtiComponent $component) {
        $renderers = $this->getRenderers();
        $className = $component->getQtiClassName();
        
        if (isset($renderers[$className]) === true) {
            return $renderers[$className];
        }
        else {
            $msg = "No AbstractRenderer implementation registered for QTI class name '${className}'.";
            throw new RenderingException($msg, RenderingException::NO_RENDERER);
        }
    }
    
    /**
     * Get the stack of rendered components stored for a later use
     * by AbstractRenderer objects.
     * 
     * @return SplStack
     */
    protected function getRenderingStack() {
        return $this->renderingStack;
    }
    
    /**
     * Set the stack of rendered components stored
     * for a later use by AbstractRenderer objects.
     * 
     * @param SplStack $renderingStack
     */
    protected function setRenderingStack(SplStack $renderingStack) {
        $this->renderingStack = $renderingStack;
    }
    
    /**
     * Set the stack where encountered xml:base values will
     * be stored.
     * 
     * @param SplStack $xmlBaseStack
     */
    protected function setXmlBaseStack(SplStack $xmlBaseStack) {
        $this->xmlBaseStack = $xmlBaseStack;
    }
    
    /**
     * Get the stack where encountered xml:base values will be
     * stored.
     * 
     * @return SplStack
     */
    protected function getXmlBaseStack() {
        return $this->xmlBaseStack;
    }
    
    /**
     * Store a rendered component as a rendering for a later use
     * by AbstractRenderer objects.
     * 
     * @param QtiComponent $component The $component from which the rendering was made.
     * @param mixed $rendering A component rendered in another format.
     */
    public function storeRendering(QtiComponent $component, $rendering) {
        $this->getRenderingStack()->push(array($component, $rendering));
    }
    
    /**
     * Get the renderings related to the children of $component.
     * 
     * @param QtiComponent $component A QtiComponent object to be rendered.
     * @return array
     */
    public function getChildrenRenderings(QtiComponent $component) {
        
        $returnValue = array();
            
        foreach ($component->getComponents() as $c) {
            
            if (count($this->getRenderingStack()) > 0) {
                list($renderedComponent, $rendering) = $this->getRenderingStack()->pop();
                
                if ($c === $renderedComponent) {
                    $returnValue[] = $rendering;
                }
                else {
                    // repush...
                    $this->storeRendering($renderedComponent, $rendering);
                }
            }
        }
        
        return $returnValue;
    }
    
    /**
     * Reset the engine to its initial state, in order
     * to be ready for reuse i.e. render a new component.
     */
    public function reset() {
        $this->setExploration(new SplStack());
        $this->setExplorationMarker(array());
        $this->setLastRendering(null);
        $this->setRenderingStack(new SplStack());
        $this->setXmlBaseStack(new SplStack());
    }
    
    /**
     * Register the value of xml:base of the currently explored component
     * into the xmlBaseStack.
     */
    protected function registerXmlBase() {
        $c = $this->getExploredComponent();
        $this->getXmlBaseStack()->push(($c instanceof Flow) ? $c->getXmlBase() : '');
    }
    
    /**
     * Resolve what is the base URI to be used for the currently explored component.
     * 
     * @return string A URI or the empty string ('') if no base URI could be resolved.
     */
    protected function resolveXmlBase() {
        $stack = $this->getXmlBaseStack();
        $stack->rewind();
        
        $resolvedBase = '';
        
        while ($stack->valid() === true) {
            
            if (($currentBase = $stack->current()) !== '') {
                if ($resolvedBase === '') {
                    $resolvedBase = $currentBase;
                }
                else {
                    $resolvedBase = Uri::rtrim($currentBase) . '/' . Uri::ltrim($resolvedBase);
                }
            }
            
            $stack->next();
        }
        
        if ($stack->count() > 0) {
            $stack->pop();
        }
        
        return $resolvedBase;
    }
    
    public function setChoiceShowHidePolicy($policy) {
        $this->choiceShowHidePolicy = $policy;
    }
    
    public function getChoiceShowHidePolicy() {
        return $this->choiceShowHidePolicy;
    }
    
    public function setFeedbackShowHidePolicy($policy) {
        $this->feedbackShowHidePolicy = $policy;
    }
    
    public function getFeedbackShowHidePolicy() {
        return $this->feedbackShowHidePolicy;
    }
    
    public function setViewPolicy($policy) {
        $this->viewPolicy = $policy;
    }
    
    public function getViewPolicy() {
        return $this->viewPolicy;
    }
    
    public function setViews(ViewCollection $views) {
        $this->views = $views;
    }
    
    public function getViews() {
        return $this->views;
    }
    
    public function setState(State $state) {
        $this->state = $state;
    }
    
    public function getState() {
        return $this->state;
    }
}