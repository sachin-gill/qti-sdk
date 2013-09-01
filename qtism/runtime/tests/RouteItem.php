<?php

namespace qtism\runtime\tests;

use qtism\data\rules\BranchRule;

use qtism\data\rules\BranchRuleCollection;
use qtism\data\AssessmentSection;
use qtism\data\TestPart;
use qtism\data\AssessmentItemRef;

/**
 * The RouteItem class describes the composite items of a Route object.
 * 
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 *
 */
class RouteItem {
    
    /**
     * The AssessmentItemRef object bound to the RouteItem.
     * 
     * @var AssessmentItemRef
     */
    private $assessmentItemRef;
    
    /**
     * The TestPart object bound to the RouteItem.
     * 
     * @var TestPart
     */
    private $testPart;
    
    /**
     * The AssessmentSection object bound to the RouteItem.
     * 
     * @var AssessmentSection 
     */
    private $assessmentSection;
    
    /**
     * The BranchRule objects to be applied after the RouteItem.
     * 
     * @var BranchRuleCollection
     */
    private $branchRules;
    
    /**
     * The occurence number.
     * 
     * @var integer
     */
    private $occurence = 0;
    
    /**
     * Create a new RouteItem object. The $assessmentSection argument might be null if and only if the
     * RouteItem does not belong to any visible AssessmentSection object.
     * 
     * @param AssessmentItemRef $assessmentItemRef The AssessmentItemRef object bound to the RouteItem.
     * @param AssessmentSection $assessmentSection The AssessmentSection object bound to the RouteItem.
     * @param TestPart $testPart The TestPart object bound to the RouteItem.
     */
    public function __construct(AssessmentItemRef $assessmentItemRef, AssessmentSection $assessmentSection = null, TestPart $testPart) {
        $this->setAssessmentItemRef($assessmentItemRef);
        $this->setAssessmentSection($assessmentSection);
        $this->setTestPart($testPart);
        $this->setBranchRules(new BranchRuleCollection());
        
        $this->addBranchRules($assessmentItemRef->getBranchRules());
    }
    
    /**
     * Set the AssessmentItemRef object bound to the RouteItem.
     * 
     * @param AssessmentItemRef $assessmentItemRef An AssessmentItemRef object.
     */
    public function setAssessmentItemRef(AssessmentItemRef $assessmentItemRef) {
        $this->assessmentItemRef = $assessmentItemRef;
    }
    
    /**
     * Get the AssessmentItemRef object bound to the RouteItem.
     * 
     * @return AssessmentItemRef An AssessmentItemRef object.
     */
    public function getAssessmentItemRef() {
        return $this->assessmentItemRef;
    }
    
    /**
     * Set the TestPart object bound to the RouteItem.
     * 
     * @param TestPart $testPart A TestPart object.
     */
    public function setTestPart(TestPart $testPart) {
        $this->testPart = $testPart;
    }
    
    /**
     * Get the TestPart object bound to the RouteItem.
     * 
     * @return TestPart A TestPart object.
     */
    public function getTestPart() {
        return $this->testPart;
    }
    
    /**
     * Set the AssessmentSection object bound to the RouteItem.
     * 
     * @param AssessmentSection $assessmentSection
     */
    public function setAssessmentSection(AssessmentSection $assessmentSection = null) {
        $this->assessmentSection = $assessmentSection;
    }
    
    /**
     * Set the occurence number.
     * 
     * @param integer $occurence An occurence number.
     */
    public function setOccurence($occurence) {
        $this->occurence = $occurence;
    }
    
    /**
     * Get the occurence number.
     * 
     * @return integer An occurence number.
     */
    public function getOccurence() {
        return $this->occurence;
    }
    
    /**
     * Get the BranchRule objects to be applied after the RouteItem.
     * 
     * @return BranchRuleCollection A collection of BranchRule objects.
     */
    public function getBranchRules() {
        return $this->branchRules;
    }
    
    /**
     * Set the BranchRule objects to be applied after the RouteItem.
     * 
     * @param BranchRuleCollection $branchRules A collection of BranchRule objects.
     */
    protected function setBranchRules(BranchRuleCollection $branchRules) {
        $this->branchRules = $branchRules;
    }
    
    /**
     * Add a BranchRule object to be applied after the RouteItem.
     * 
     * @param BranchRule $branchRule A BranchRule object to be added.
     */
    public function addBranchRule(BranchRule $branchRule) {
        $this->getBranchRules()->attach($branchRule);
    }
    
    /**
     * Add some BranchRule objects to be applied after the RouteItem.
     * 
     * @param BranchRuleCollection $branchRules A collection of BranchRule object.
     */
    public function addBranchRules(BranchRuleCollection $branchRules) {
        foreach ($branchRules as $branchRule) {
            $this->addBranchRule($branchRule);
        }
    }
    
    /**
     * Increment the occurence number by 1.
     * 
     */
    public function incrementOccurenceNumber() {
        $this->setOccurence($this->getOccurence() + 1);
    }
    
    /**
     * Get the AssessmentSection object bound to the RouteItem.
     * 
     * @return AssessmentSection An AssessmentSection object.
     */
    public function getAssessmentSection() {
        return $this->assessmentSection;
    }
}