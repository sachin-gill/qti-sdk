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

use qtism\data\QtiComponent;
use \SplStack;

/**
 * An AbstractRenderingContext object represents the context in which
 * a hierarchy of QtiComponents are rendered by a set of
 * AbstractRenderer objects.
 * 
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 *
 */
abstract class AbstractRenderingContext {
    
    /**
     * The stack where rendered components in other
     * constitution are stored for a later use.
     * 
     * @var SplStack
     */
    private $renderingStack;
    
    /**
     * An associative array where keys are QTI class names
     * and values are AbstractRenderer objects.
     * 
     * @var array
     */
    private $renderers;
    
    /**
     * Create a new AbstractRenderingContext object.
     * 
     */
    public function __construct() {
        $this->setRenderers(array());
        $this->setRenderingStack(new SplStack());
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
     * Register a $renderer object to a given $qtiClassName.
     * 
     * @param string $qtiClassName A QTI class name.
     * @param AbstractRenderer $renderer An AbstractRenderer object.
     */
    protected function registerRenderer($qtiClassName, AbstractRenderer $renderer) {
        $renderer->setRenderingContext($this);
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
            $msg = "No AbstractRenderer implementation registered for QTI class name '${qtiClassName}'.";
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
     * Store a rendered component as a rendering for a later use
     * by AbstractRenderer objects.
     * 
     * @param mixed $rendering A component rendered in another format.
     */
    public function storeRendering($rendering) {
        $this->getRenderingStack()->push($rendering);
    }
    
    /**
     * Get the renderings related to the children of $component.
     * 
     * @param QtiComponent $component A QtiComponent object to be rendered.
     */
    public function getChildrenRenderings(QtiComponent $component) {
        $childCount = count($component->getComponents());
        $returnValue = array();
        
        for ($i = 0; $i < $childCount; $i++) {
            $returnValue[] = $this->getRenderingStack()->pop();
        }
        
        return array_reverse($returnValue);
    }
}