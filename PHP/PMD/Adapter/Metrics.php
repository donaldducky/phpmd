<?php
/**
 * This file is part of PHP_PMD.
 *
 * PHP Version 5
 *
 * Copyright (c) 2009, Manuel Pichler <mapi@pdepend.org>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Manuel Pichler nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   PHP
 * @package    PHP_PMD
 * @subpackage Adapter
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2009 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    SVN: $Id$
 * @link       http://www.pdepend.org/pmd
 */

require_once 'PHP/Depend/Log/LoggerI.php';
require_once 'PHP/Depend/Log/CodeAwareI.php';
require_once 'PHP/Depend/Visitor/AbstractVisitor.php';

require_once 'PHP/PMD/Node/Class.php';
require_once 'PHP/PMD/Node/Function.php';
require_once 'PHP/PMD/Node/Interface.php';
require_once 'PHP/PMD/Node/Method.php';

/**
 * This is an adapter for node and project metrics generated by PHP_Depend.
 *
 * @category   PHP
 * @package    PHP_PMD
 * @subpackage Adapter
 * @author     Manuel Pichler <mapi@pdepend.org>
 * @copyright  2009 Manuel Pichler. All rights reserved.
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://www.pdepend.org/pmd
 */
class PHP_PMD_Adapter_Metrics
       extends PHP_Depend_Visitor_AbstractVisitor
    implements PHP_Depend_Log_LoggerI,
               PHP_Depend_Log_CodeAwareI
{
    private $_ruleSets = array();

    private $_analyzers = array();

    private $_code = null;

    /**
     * The violation report used by this PHP_Depend adapter.
     *
     * @var PHP_PMD_Report $_report
     */
    private $_report = null;

    public function addRuleSet(PHP_PMD_RuleSet $ruleSet)
    {
        $this->_ruleSets[] = $ruleSet;
    }

    /**
     * Returns the violation report used by the rule-set.
     *
     * @return PHP_PMD_Report
     */
    public function getReport()
    {
        return $this->_report;
    }

    /**
     * Sets the violation report used by the rule-set.
     *
     * @param PHP_PMD_Report $report The violation report to use.
     *
     * @return void
     */
    public function setReport(PHP_PMD_Report $report)
    {
        $this->_report = $report;
    }

    public function log(PHP_Depend_Metrics_AnalyzerI $analyzer)
    {
        $this->_analyzers[] = $analyzer;
    }

    public function close()
    {
        foreach ($this->_code as $node) {
            $node->accept($this);
        }
    }

    public function getAcceptedAnalyzers()
    {
        return array('PHP_Depend_Metrics_NodeAwareI');
    }

    public function visitClass(PHP_Depend_Code_Class $node)
    {
        $this->_apply(new PHP_PMD_Node_Class($node));
        parent::visitClass($node);
    }

    public function visitFunction(PHP_Depend_Code_Function $node)
    {
        $this->_apply(new PHP_PMD_Node_Function($node));
    }

    public function visitMethod(PHP_Depend_Code_Method $node)
    {
        $this->_apply(new PHP_PMD_Node_Method($node));
    }

    /**
     * Sets the context code nodes.
     *
     * @param PHP_Depend_Code_NodeIterator $code The code nodes.
     *
     * @return void
     */
    public function setCode(PHP_Depend_Code_NodeIterator $code)
    {
        $this->_code = $code;
    }

    private function _apply(PHP_PMD_AbstractNode $node)
    {
        $this->_collectMetrics($node);
        foreach ($this->_ruleSets as $ruleSet) {
            $ruleSet->setReport($this->_report);
            $ruleSet->apply($node);
        }
    }

    private function _collectMetrics(PHP_PMD_AbstractNode $node)
    {
        $metrics = array();

        $pdepend = $node->getNode();
        foreach ($this->_analyzers as $analyzer) {
            $metrics = array_merge($metrics, $analyzer->getNodeMetrics($pdepend));
        }
        $node->setMetrics($metrics);
    }
}
?>
