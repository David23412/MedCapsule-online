<?php

namespace App\Services\Audit\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Complexity Visitor for AST Analysis
 * 
 * Calculates cyclomatic complexity and other code metrics
 */
class ComplexityVisitor extends NodeVisitorAbstract
{
    private int $cyclomaticComplexity = 1; // Base complexity
    private int $methodCount = 0;
    private int $classCount = 0;
    private int $functionCount = 0;
    private array $evidenceTrace = [];

    public function enterNode(Node $node)
    {
        // Increment complexity for decision points
        if ($node instanceof Node\Stmt\If_) {
            $this->cyclomaticComplexity++;
            $this->evidenceTrace[] = [
                'type' => 'if_statement',
                'line' => $node->getStartLine(),
                'complexity_increment' => 1
            ];
        } elseif ($node instanceof Node\Stmt\ElseIf_) {
            $this->cyclomaticComplexity++;
            $this->evidenceTrace[] = [
                'type' => 'elseif_statement',
                'line' => $node->getStartLine(),
                'complexity_increment' => 1
            ];
        } elseif ($node instanceof Node\Stmt\While_) {
            $this->cyclomaticComplexity++;
            $this->evidenceTrace[] = [
                'type' => 'while_loop',
                'line' => $node->getStartLine(),
                'complexity_increment' => 1
            ];
        } elseif ($node instanceof Node\Stmt\For_) {
            $this->cyclomaticComplexity++;
            $this->evidenceTrace[] = [
                'type' => 'for_loop',
                'line' => $node->getStartLine(),
                'complexity_increment' => 1
            ];
        } elseif ($node instanceof Node\Stmt\Foreach_) {
            $this->cyclomaticComplexity++;
            $this->evidenceTrace[] = [
                'type' => 'foreach_loop',
                'line' => $node->getStartLine(),
                'complexity_increment' => 1
            ];
        } elseif ($node instanceof Node\Stmt\Switch_) {
            $this->cyclomaticComplexity++;
            $this->evidenceTrace[] = [
                'type' => 'switch_statement',
                'line' => $node->getStartLine(),
                'complexity_increment' => 1
            ];
        } elseif ($node instanceof Node\Stmt\Case_) {
            $this->cyclomaticComplexity++;
            $this->evidenceTrace[] = [
                'type' => 'case_statement',
                'line' => $node->getStartLine(),
                'complexity_increment' => 1
            ];
        } elseif ($node instanceof Node\Stmt\Catch_) {
            $this->cyclomaticComplexity++;
            $this->evidenceTrace[] = [
                'type' => 'catch_block',
                'line' => $node->getStartLine(),
                'complexity_increment' => 1
            ];
        } elseif ($node instanceof Node\Expr\Ternary) {
            $this->cyclomaticComplexity++;
            $this->evidenceTrace[] = [
                'type' => 'ternary_operator',
                'line' => $node->getStartLine(),
                'complexity_increment' => 1
            ];
        } elseif ($node instanceof Node\Expr\BinaryOp\LogicalAnd || 
                  $node instanceof Node\Expr\BinaryOp\LogicalOr ||
                  $node instanceof Node\Expr\BinaryOp\BooleanAnd ||
                  $node instanceof Node\Expr\BinaryOp\BooleanOr) {
            $this->cyclomaticComplexity++;
            $this->evidenceTrace[] = [
                'type' => 'logical_operator',
                'line' => $node->getStartLine(),
                'complexity_increment' => 1
            ];
        }

        // Count structural elements
        if ($node instanceof Node\Stmt\ClassMethod) {
            $this->methodCount++;
        } elseif ($node instanceof Node\Stmt\Function_) {
            $this->functionCount++;
        } elseif ($node instanceof Node\Stmt\Class_) {
            $this->classCount++;
        }

        return null;
    }

    public function getCyclomaticComplexity(): int
    {
        return $this->cyclomaticComplexity;
    }

    public function getMethodCount(): int
    {
        return $this->methodCount;
    }

    public function getClassCount(): int
    {
        return $this->classCount;
    }

    public function getFunctionCount(): int
    {
        return $this->functionCount;
    }

    public function getEvidenceTrace(): array
    {
        return $this->evidenceTrace;
    }
}