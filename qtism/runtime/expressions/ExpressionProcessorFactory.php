<?php

namespace qtism\runtime\expressions;

use qtism\data\QtiComponent;
use qtism\runtime\common\ProcessorFactory;
use qtism\runtime\common\Processable;
use qtism\runtime\expressions\operators\OperatorProcessor;
use qtism\data\expressions\Expression;
use \RuntimeException;

/**
 * The ExpressionProcessorFactory class provides a way to build
 * an appropriate ExpressionProcessor on basis of QTI Data Model Expression
 * objects.
 * 
 * @author Jérôme Bogaerts <jerome@taotesting.com>
 *
 */
class ExpressionProcessorFactory implements ProcessorFactory {
	
	/**
	 * Create a new ExpressionProcessorFactory object.
	 * 
	 */
	public function __construct() {
		
	}
	
	/**
	 * Create the ExpressionProcessor object able to process the 
	 * given $expression.
	 * 
	 * @param QtiComponent $expression An Expression object you want to get the related processor.
	 * @return Processable The related ExpressionProcessor object.
	 * @throws RuntimeException If no ExpressionProcessor can be found for the given $expression.
	 */
	public function createProcessor(QtiComponent $expression) {
		$qtiClassName = ucfirst($expression->getQtiClassName());
		$nsPackage = 'qtism\\runtime\\expressions\\';
		$className =  $nsPackage . $qtiClassName . 'Processor';
		
		if (class_exists($className) === true) {
			// This is a simple expression to be processed.
			return new $className($expression);
		}
		
		$msg = "The QTI expression class '${qtiClassName}' has no dedicated ExpressionProcessor class.";
		throw new RuntimeException($msg);
	}
}