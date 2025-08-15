<?php

namespace App\Services\Audit\Components;

use App\Services\Audit\Models\ConcreteFinding;
use App\Services\Audit\Reports\PhaseResult;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Exception;

/**
 * Interdependency Chain Tracker with LETHAL GOLDEN STANDARD Implementation
 * 
 * Tracks and analyzes complex interdependencies between events, listeners, queues,
 * services, and other components with surgical precision and visual mapping
 */
class InterdependencyTracker
{
    private FileScanner $fileScanner;
    private array $findings = [];
    private int $findingCounter = 1;
    private array $dependencyChains = [];
    private array $eventListenerMap = [];
    private array $serviceMap = [];
    private array $couplingScores = [];
    private array $dependencyGraph = [];

    public function __construct(FileScanner $fileScanner)
    {
        $this->fileScanner = $fileScanner;
    }

    /**
     * Execute comprehensive interdependency analysis with LETHAL PRECISION
     */
    public function executeInterdependencyAnalysis(string $projectPath): PhaseResult
    {
        Log::info('🔍 Starting LETHAL Interdependency Chain Analysis');
        
        $startTime = microtime(true);
        $this->findings = [];
        
        try {
            // Scan directory first to populate file lists
            $this->fileScanner->scanDirectory($projectPath);
            
            // 1. Event Cascade Tracing (Event → Listener → Queue → Mail)
            $this->traceEventCascades();
            
            // 2. Service Dependency Mapping
            $this->mapServiceDependencies();
            
            // 3. Tight Coupling Violation Detection
            $this->detectTightCouplingViolations();
            
            // 4. Coupling Score Calculation per Class
            $this->calculateCouplingScores();
            
            // 5. Dependency Graph Generation
            $this->generateDependencyGraph();
            
            // 6. Impact Assessment for Component Changes
            $this->assessComponentChangeImpact();
            
            // 7. Chain Trace Documentation with Visual Maps
            $this->generateChainTraceDocumentation();
            
            $executionTime = microtime(true) - $startTime;
            
            $phaseResult = new PhaseResult(
                'interdependency_analysis',
                $this->findings,
                $this->generateInterdependencyMetrics(),
                $executionTime
            );
            
            Log::info('✅ Interdependency Analysis Complete', [
                'findings' => count($this->findings),
                'execution_time' => round($executionTime, 2) . 's'
            ]);
            
            return $phaseResult;
            
        } catch (\Exception $e) {
            Log::error('❌ Interdependency Analysis Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
/**
     * LETHAL: Event Cascade Tracing (Event → Listener → Queue → Mail)
     */
    public function traceEventCascades(): void
    {
        Log::info('🔍 Tracing Event Cascades');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $eventChains = [];
        $cascadeIssues = 0;
        
        // Find all events
        $events = $this->findEvents($phpFiles);
        
        // Find all listeners
        $listeners = $this->findListeners($phpFiles);
        
        // Map event-listener relationships
        $this->eventListenerMap = $this->mapEventListeners($events, $listeners);
        
        // Trace cascades for each event
        foreach ($this->eventListenerMap as $eventClass => $eventListeners) {
            $cascade = $this->traceSingleEventCascade($eventClass, $eventListeners, $phpFiles);
            
            if (!empty($cascade)) {
                $eventChains[$eventClass] = $cascade;
                
                // Check for complex cascades (more than 3 levels deep)
                $maxDepth = $this->calculateCascadeDepth($cascade);
                
                if ($maxDepth > 3) {
                    $cascadeIssues++;
                    
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        'medium',
                        "Complex event cascade detected: {$eventClass} ({$maxDepth} levels deep)",
                        'interdependency',
                        'complex_cascade'
                    );
                    
                    $finding->addFileReference($this->findEventFile($eventClass, $phpFiles), 1, "Event: {$eventClass}");
                    $finding->addMetric('cascade_depth', $maxDepth, 'levels');
                    $finding->addMetric('listeners_count', count($eventListeners), 'listeners');
                    $finding->addMetric('complexity_score', $maxDepth * count($eventListeners), 'score');
                    
                    $finding->addEvidence('static_analysis', [
                        'event_class' => $eventClass,
                        'cascade_chain' => $cascade,
                        'max_depth' => $maxDepth,
                        'total_listeners' => count($eventListeners)
                    ], 'Complex event cascade chain detected');
                    
                    $finding->setRecommendation('Consider breaking down complex event cascades into simpler chains');
                    $finding->setEstimatedHours(3);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        $this->dependencyChains['events'] = $eventChains;
        Log::info("Found {$cascadeIssues} complex event cascades");
    }

    /**
     * Find all events in the codebase
     */
    private function findEvents(array $phpFiles): array
    {
        $events = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            
            // Look for event classes
            if (preg_match('/class\s+(\w+).*(?:extends\s+.*Event|implements.*ShouldBroadcast)/', $content, $matches)) {
                $events[] = [
                    'class' => $matches[1],
                    'file' => $file['path'],
                    'namespace' => $this->extractNamespace($content)
                ];
            }
            
            // Look for event() calls
            if (preg_match_all('/event\s*\(\s*new\s+([A-Za-z\\\\]+)/', $content, $matches)) {
                foreach ($matches[1] as $eventClass) {
                    $events[] = [
                        'class' => $eventClass,
                        'file' => $file['path'],
                        'type' => 'dispatched'
                    ];
                }
            }
        }
        
        return array_unique($events, SORT_REGULAR);
    }

    /**
     * Find all listeners in the codebase
     */
    private function findListeners(array $phpFiles): array
    {
        $listeners = [];
        
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            
            // Look for listener classes
            if (preg_match('/class\s+(\w+).*(?:implements.*ShouldQueue|handle\s*\()/', $content, $matches)) {
                $listeners[] = [
                    'class' => $matches[1],
                    'file' => $file['path'],
                    'namespace' => $this->extractNamespace($content),
                    'queued' => strpos($content, 'ShouldQueue') !== false
                ];
            }
        }
        
        return $listeners;
    }

    /**
     * Map event-listener relationships
     */
    private function mapEventListeners(array $events, array $listeners): array
    {
        $map = [];
        
        // Check EventServiceProvider for explicit mappings
        $eventServiceProvider = $this->findEventServiceProvider();
        
        if ($eventServiceProvider) {
            $content = file_get_contents($eventServiceProvider);
            
            // Parse the listen array
            if (preg_match('/protected\s+\$listen\s*=\s*\[(.*?)\];/s', $content, $matches)) {
                $listenArray = $matches[1];
                
                // Extract event => listener mappings
                preg_match_all('/[\'"]([^\'"]*)[\'"]\s*=>\s*\[(.*?)\]/s', $listenArray, $mappings);
                
                for ($i = 0; $i < count($mappings[1]); $i++) {
                    $eventClass = $mappings[1][$i];
                    $listenersStr = $mappings[2][$i];
                    
                    preg_match_all('/[\'"]([^\'"]*)[\'"]/s', $listenersStr, $listenerMatches);
                    
                    $map[$eventClass] = $listenerMatches[1];
                }
            }
        }
        
        return $map;
    }

    /**
     * Trace a single event cascade
     */
    private function traceSingleEventCascade(string $eventClass, array $listeners, array $phpFiles): array
    {
        $cascade = [
            'event' => $eventClass,
            'listeners' => [],
            'queues' => [],
            'mails' => [],
            'notifications' => []
        ];
        
        foreach ($listeners as $listenerClass) {
            $listenerInfo = $this->analyzeListener($listenerClass, $phpFiles);
            
            $cascade['listeners'][] = $listenerInfo;
            
            // Check if listener dispatches jobs
            if (!empty($listenerInfo['dispatched_jobs'])) {
                $cascade['queues'] = array_merge($cascade['queues'], $listenerInfo['dispatched_jobs']);
            }
            
            // Check if listener sends mail
            if (!empty($listenerInfo['sent_mails'])) {
                $cascade['mails'] = array_merge($cascade['mails'], $listenerInfo['sent_mails']);
            }
            
            // Check if listener sends notifications
            if (!empty($listenerInfo['sent_notifications'])) {
                $cascade['notifications'] = array_merge($cascade['notifications'], $listenerInfo['sent_notifications']);
            }
        }
        
        return $cascade;
    }

    /**
     * Analyze a listener for its dependencies
     */
    private function analyzeListener(string $listenerClass, array $phpFiles): array
    {
        $listenerFile = $this->findClassFile($listenerClass, $phpFiles);
        
        if (!$listenerFile) {
            return ['class' => $listenerClass, 'file' => null];
        }
        
        $content = file_get_contents($listenerFile);
        
        $analysis = [
            'class' => $listenerClass,
            'file' => str_replace(base_path() . '/', '', $listenerFile),
            'dispatched_jobs' => [],
            'sent_mails' => [],
            'sent_notifications' => []
        ];
        
        // Find dispatched jobs
        if (preg_match_all('/dispatch\s*\(\s*new\s+([A-Za-z\\\\]+)/', $content, $matches)) {
            $analysis['dispatched_jobs'] = $matches[1];
        }
        
        // Find sent mails
        if (preg_match_all('/Mail::(?:to|send)\s*\([^)]*new\s+([A-Za-z\\\\]+)/', $content, $matches)) {
            $analysis['sent_mails'] = $matches[1];
        }
        
        // Find sent notifications
        if (preg_match_all('/notify\s*\(\s*new\s+([A-Za-z\\\\]+)/', $content, $matches)) {
            $analysis['sent_notifications'] = $matches[1];
        }
        
        return $analysis;
    }

    /**
     * Calculate cascade depth
     */
    private function calculateCascadeDepth(array $cascade): int
    {
        $depth = 1; // Event itself
        
        if (!empty($cascade['listeners'])) {
            $depth = 2; // Event → Listener
            
            foreach ($cascade['listeners'] as $listener) {
                if (!empty($listener['dispatched_jobs'])) {
                    $depth = max($depth, 3); // Event → Listener → Queue
                }
                
                if (!empty($listener['sent_mails']) || !empty($listener['sent_notifications'])) {
                    $depth = max($depth, 3); // Event → Listener → Mail/Notification
                }
            }
        }
        
        return $depth;
    }

    /**
     * Find EventServiceProvider file
     */
    private function findEventServiceProvider(): ?string
    {
        $providerPath = base_path('app/Providers/EventServiceProvider.php');
        
        return file_exists($providerPath) ? $providerPath : null;
    }

    /**
     * Extract namespace from file content
     */
    private function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Find class file
     */
    private function findClassFile(string $className, array $phpFiles): ?string
    {
        foreach ($phpFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            
            if (preg_match('/class\s+' . preg_quote($className, '/') . '\b/', $content)) {
                return $file['absolute_path'];
            }
        }
        
        return null;
    }

    /**
     * Find event file
     */
    private function findEventFile(string $eventClass, array $phpFiles): string
    {
        $file = $this->findClassFile($eventClass, $phpFiles);
        
        return $file ? str_replace(base_path() . '/', '', $file) : 'Unknown';
    }    /**

     * LETHAL: Service Dependency Mapping
     */
    public function mapServiceDependencies(): void
    {
        Log::info('🔍 Mapping Service Dependencies');
        
        $phpFiles = $this->fileScanner->getPHPFiles();
        $serviceFiles = array_filter($phpFiles, fn($file) => strpos($file['path'], 'Services/') !== false);
        
        $dependencyIssues = 0;
        
        foreach ($serviceFiles as $file) {
            $content = file_get_contents($file['absolute_path']);
            $className = $this->extractClassName($content);
            
            if (!$className) continue;
            
            $dependencies = $this->analyzeDependencies($content);
            
            $this->serviceMap[$className] = [
                'file' => $file['path'],
                'dependencies' => $dependencies,
                'dependency_count' => count($dependencies['classes']),
                'coupling_score' => $this->calculateClassCouplingScore($dependencies)
            ];
            
            // Check for excessive dependencies
            if (count($dependencies['classes']) > 10) {
                $dependencyIssues++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'high',
                    "Service has excessive dependencies: {$className} (" . count($dependencies['classes']) . " dependencies)",
                    'interdependency',
                    'excessive_dependencies'
                );
                
                $finding->addFileReference($file['path'], 1, "Service: {$className}");
                $finding->addMetric('dependency_count', count($dependencies['classes']), 'dependencies');
                $finding->addMetric('coupling_score', $this->serviceMap[$className]['coupling_score'], 'score');
                
                $finding->addEvidence('static_analysis', [
                    'service_class' => $className,
                    'dependencies' => array_slice($dependencies['classes'], 0, 15), // Limit for readability
                    'total_dependencies' => count($dependencies['classes']),
                    'coupling_types' => $dependencies['types']
                ], 'Excessive service dependencies detected');
                
                $finding->setRecommendation('Refactor service to reduce dependencies and improve maintainability');
                $finding->setEstimatedHours(6);
                
                $this->findings[] = $finding;
            }
        }
        
        Log::info("Found {$dependencyIssues} services with excessive dependencies");
    }

    /**
     * Analyze dependencies in a class
     */
    private function analyzeDependencies(string $content): array
    {
        $dependencies = [
            'classes' => [],
            'types' => []
        ];
        
        // Find use statements
        if (preg_match_all('/use\s+([^;]+);/', $content, $matches)) {
            foreach ($matches[1] as $useStatement) {
                $className = trim($useStatement);
                
                // Skip Laravel framework classes for cleaner analysis
                if (!$this->isFrameworkClass($className)) {
                    $dependencies['classes'][] = $className;
                    $dependencies['types'][] = 'use_statement';
                }
            }
        }
        
        // Find constructor dependencies
        if (preg_match('/public\s+function\s+__construct\s*\(([^)]*)\)/', $content, $matches)) {
            $constructorParams = $matches[1];
            
            if (preg_match_all('/([A-Za-z\\\\]+)\s+\$\w+/', $constructorParams, $paramMatches)) {
                foreach ($paramMatches[1] as $paramType) {
                    if (!$this->isFrameworkClass($paramType)) {
                        $dependencies['classes'][] = $paramType;
                        $dependencies['types'][] = 'constructor_injection';
                    }
                }
            }
        }
        
        // Find method dependencies (new instances)
        if (preg_match_all('/new\s+([A-Za-z\\\\]+)\s*\(/', $content, $matches)) {
            foreach ($matches[1] as $newClass) {
                if (!$this->isFrameworkClass($newClass)) {
                    $dependencies['classes'][] = $newClass;
                    $dependencies['types'][] = 'direct_instantiation';
                }
            }
        }
        
        // Remove duplicates
        $dependencies['classes'] = array_unique($dependencies['classes']);
        
        return $dependencies;
    }

    /**
     * Check if a class is a framework class
     */
    private function isFrameworkClass(string $className): bool
    {
        $frameworkPrefixes = [
            'Illuminate\\',
            'Laravel\\',
            'Symfony\\',
            'Carbon\\',
            'Monolog\\',
            'Psr\\',
            'Exception',
            'stdClass'
        ];
        
        foreach ($frameworkPrefixes as $prefix) {
            if (strpos($className, $prefix) === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * LETHAL: Detect Tight Coupling Violations
     */
    public function detectTightCouplingViolations(): void
    {
        Log::info('🔍 Detecting Tight Coupling Violations');
        
        $couplingViolations = 0;
        
        foreach ($this->serviceMap as $className => $serviceInfo) {
            $violations = $this->analyzeTightCoupling($className, $serviceInfo);
            
            if (!empty($violations)) {
                $couplingViolations += count($violations);
                
                foreach ($violations as $violation) {
                    $finding = new ConcreteFinding(
                        'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                        $violation['severity'],
                        $violation['description'],
                        'interdependency',
                        'tight_coupling'
                    );
                    
                    $finding->addFileReference($serviceInfo['file'], 1, "Service: {$className}");
                    $finding->addMetric('coupling_type', $violation['type'], 'violation_type');
                    $finding->addMetric('coupling_score', $serviceInfo['coupling_score'], 'score');
                    
                    $finding->addEvidence('static_analysis', [
                        'service_class' => $className,
                        'violation_type' => $violation['type'],
                        'violation_details' => $violation['details'],
                        'coupling_score' => $serviceInfo['coupling_score']
                    ], 'Tight coupling violation detected');
                    
                    $finding->setRecommendation($violation['recommendation']);
                    $finding->setEstimatedHours($violation['effort_hours']);
                    
                    $this->findings[] = $finding;
                }
            }
        }
        
        Log::info("Found {$couplingViolations} tight coupling violations");
    }

    /**
     * Analyze tight coupling for a service
     */
    private function analyzeTightCoupling(string $className, array $serviceInfo): array
    {
        $violations = [];
        
        // Check for excessive dependencies
        if ($serviceInfo['dependency_count'] > 15) {
            $violations[] = [
                'type' => 'excessive_dependencies',
                'severity' => 'high',
                'description' => "Service {$className} has {$serviceInfo['dependency_count']} dependencies (>15)",
                'details' => ['dependency_count' => $serviceInfo['dependency_count']],
                'recommendation' => 'Break down service into smaller, focused services',
                'effort_hours' => 8
            ];
        }
        
        // Check for circular dependencies (simplified check)
        $circularDeps = $this->checkCircularDependencies($className, $serviceInfo);
        if (!empty($circularDeps)) {
            $violations[] = [
                'type' => 'circular_dependency',
                'severity' => 'critical',
                'description' => "Circular dependency detected in {$className}",
                'details' => ['circular_dependencies' => $circularDeps],
                'recommendation' => 'Refactor to eliminate circular dependencies using interfaces or events',
                'effort_hours' => 12
            ];
        }
        
        // Check coupling score
        if ($serviceInfo['coupling_score'] > 8.0) {
            $violations[] = [
                'type' => 'high_coupling_score',
                'severity' => 'medium',
                'description' => "High coupling score for {$className}: {$serviceInfo['coupling_score']}",
                'details' => ['coupling_score' => $serviceInfo['coupling_score']],
                'recommendation' => 'Reduce coupling by using dependency injection and interfaces',
                'effort_hours' => 4
            ];
        }
        
        return $violations;
    }

    /**
     * Check for circular dependencies (simplified)
     */
    private function checkCircularDependencies(string $className, array $serviceInfo): array
    {
        $circular = [];
        
        // This is a simplified check - in a real implementation, 
        // you'd need a more sophisticated graph traversal algorithm
        foreach ($serviceInfo['dependencies']['classes'] as $dependency) {
            if (isset($this->serviceMap[$dependency])) {
                $depService = $this->serviceMap[$dependency];
                
                if (in_array($className, $depService['dependencies']['classes'])) {
                    $circular[] = $dependency;
                }
            }
        }
        
        return $circular;
    }

    /**
     * LETHAL: Calculate Coupling Scores
     */
    public function calculateCouplingScores(): void
    {
        Log::info('🔍 Calculating Coupling Scores');
        
        foreach ($this->serviceMap as $className => $serviceInfo) {
            $score = $this->calculateClassCouplingScore($serviceInfo['dependencies']);
            $this->couplingScores[$className] = $score;
        }
        
        // Find classes with highest coupling scores
        arsort($this->couplingScores);
        $topCoupled = array_slice($this->couplingScores, 0, 5, true);
        
        foreach ($topCoupled as $className => $score) {
            if ($score > 7.0) {
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'medium',
                    "High coupling score detected: {$className} (Score: {$score})",
                    'interdependency',
                    'high_coupling_score'
                );
                
                $serviceFile = $this->serviceMap[$className]['file'] ?? 'Unknown';
                $finding->addFileReference($serviceFile, 1, "Class: {$className}");
                $finding->addMetric('coupling_score', $score, 'score');
                $finding->addMetric('dependency_count', $this->serviceMap[$className]['dependency_count'] ?? 0, 'dependencies');
                
                $finding->addEvidence('static_analysis', [
                    'class_name' => $className,
                    'coupling_score' => $score,
                    'dependency_breakdown' => $this->serviceMap[$className]['dependencies'] ?? []
                ], 'High coupling score calculated');
                
                $finding->setRecommendation('Refactor to reduce coupling using dependency injection and interfaces');
                $finding->setEstimatedHours(4);
                
                $this->findings[] = $finding;
            }
        }
        
        Log::info('Calculated coupling scores for ' . count($this->couplingScores) . ' classes');
    }

    /**
     * Calculate coupling score for a class
     */
    private function calculateClassCouplingScore(array $dependencies): float
    {
        $score = 0.0;
        
        // Base score from dependency count
        $dependencyCount = count($dependencies['classes']);
        $score += min(10.0, $dependencyCount * 0.5);
        
        // Penalty for different coupling types
        $typeWeights = [
            'use_statement' => 0.1,
            'constructor_injection' => 0.3, // Better than direct instantiation
            'direct_instantiation' => 0.8   // Highest coupling
        ];
        
        foreach ($dependencies['types'] as $type) {
            $score += $typeWeights[$type] ?? 0.5;
        }
        
        return round(min(10.0, $score), 2);
    }

    /**
     * Extract class name from content
     */
    private function extractClassName(string $content): ?string
    {
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }    
/**
     * LETHAL: Generate Dependency Graph
     */
    public function generateDependencyGraph(): void
    {
        Log::info('🔍 Generating Dependency Graph');
        
        $this->dependencyGraph = [
            'nodes' => [],
            'edges' => [],
            'clusters' => []
        ];
        
        // Create nodes for each service
        foreach ($this->serviceMap as $className => $serviceInfo) {
            $this->dependencyGraph['nodes'][] = [
                'id' => $className,
                'label' => $className,
                'type' => 'service',
                'coupling_score' => $serviceInfo['coupling_score'],
                'dependency_count' => $serviceInfo['dependency_count'],
                'file' => $serviceInfo['file']
            ];
        }
        
        // Create edges for dependencies
        foreach ($this->serviceMap as $className => $serviceInfo) {
            foreach ($serviceInfo['dependencies']['classes'] as $dependency) {
                if (isset($this->serviceMap[$dependency])) {
                    $this->dependencyGraph['edges'][] = [
                        'from' => $className,
                        'to' => $dependency,
                        'type' => 'dependency',
                        'weight' => 1
                    ];
                }
            }
        }
        
        // Create clusters based on coupling scores
        $this->dependencyGraph['clusters'] = [
            'high_coupling' => array_keys(array_filter($this->couplingScores, fn($score) => $score > 7.0)),
            'medium_coupling' => array_keys(array_filter($this->couplingScores, fn($score) => $score >= 4.0 && $score <= 7.0)),
            'low_coupling' => array_keys(array_filter($this->couplingScores, fn($score) => $score < 4.0))
        ];
        
        // Generate visual representation
        $this->generateVisualDependencyMap();
        
        Log::info('Generated dependency graph with ' . count($this->dependencyGraph['nodes']) . ' nodes and ' . count($this->dependencyGraph['edges']) . ' edges');
    }

    /**
     * Generate visual dependency map
     */
    private function generateVisualDependencyMap(): void
    {
        $mermaidGraph = $this->generateMermaidGraph();
        $dotGraph = $this->generateDotGraph();
        
        // Save visual maps
        $this->saveVisualMap('mermaid', $mermaidGraph);
        $this->saveVisualMap('dot', $dotGraph);
    }

    /**
     * Generate Mermaid graph syntax
     */
    private function generateMermaidGraph(): string
    {
        $mermaid = "graph TD\n";
        
        // Add nodes with styling based on coupling score
        foreach ($this->dependencyGraph['nodes'] as $node) {
            $style = $this->getMermaidNodeStyle($node['coupling_score']);
            $mermaid .= "    {$node['id']}[{$node['label']}]{$style}\n";
        }
        
        // Add edges
        foreach ($this->dependencyGraph['edges'] as $edge) {
            $mermaid .= "    {$edge['from']} --> {$edge['to']}\n";
        }
        
        // Add styling
        $mermaid .= "\n    classDef highCoupling fill:#ff6b6b,stroke:#d63031,stroke-width:2px\n";
        $mermaid .= "    classDef mediumCoupling fill:#fdcb6e,stroke:#e17055,stroke-width:2px\n";
        $mermaid .= "    classDef lowCoupling fill:#00b894,stroke:#00a085,stroke-width:2px\n";
        
        return $mermaid;
    }

    /**
     * Generate DOT graph syntax
     */
    private function generateDotGraph(): string
    {
        $dot = "digraph DependencyGraph {\n";
        $dot .= "    rankdir=TB;\n";
        $dot .= "    node [shape=box, style=rounded];\n\n";
        
        // Add nodes
        foreach ($this->dependencyGraph['nodes'] as $node) {
            $color = $this->getDotNodeColor($node['coupling_score']);
            $dot .= "    \"{$node['id']}\" [label=\"{$node['label']}\\nScore: {$node['coupling_score']}\", fillcolor=\"{$color}\", style=\"filled,rounded\"];\n";
        }
        
        $dot .= "\n";
        
        // Add edges
        foreach ($this->dependencyGraph['edges'] as $edge) {
            $dot .= "    \"{$edge['from']}\" -> \"{$edge['to']}\";\n";
        }
        
        $dot .= "}\n";
        
        return $dot;
    }

    /**
     * Get Mermaid node style based on coupling score
     */
    private function getMermaidNodeStyle(float $score): string
    {
        if ($score > 7.0) {
            return ':::highCoupling';
        } elseif ($score >= 4.0) {
            return ':::mediumCoupling';
        } else {
            return ':::lowCoupling';
        }
    }

    /**
     * Get DOT node color based on coupling score
     */
    private function getDotNodeColor(float $score): string
    {
        if ($score > 7.0) {
            return '#ff6b6b';
        } elseif ($score >= 4.0) {
            return '#fdcb6e';
        } else {
            return '#00b894';
        }
    }

    /**
     * Save visual map to file
     */
    private function saveVisualMap(string $format, string $content): void
    {
        $outputDir = storage_path('app/audit/dependency-maps');
        
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }
        
        $filename = "dependency-graph-" . date('Y-m-d-H-i-s') . ".{$format}";
        $filepath = $outputDir . '/' . $filename;
        
        File::put($filepath, $content);
        
        Log::info("Saved {$format} dependency map: {$filepath}");
    }   
 /**
     * LETHAL: Impact Assessment for Component Changes
     */
    public function assessComponentChangeImpact(): void
    {
        Log::info('🔍 Assessing Component Change Impact');
        
        $impactAssessments = [];
        $highImpactComponents = 0;
        
        foreach ($this->serviceMap as $className => $serviceInfo) {
            $impact = $this->calculateComponentImpact($className, $serviceInfo);
            $impactAssessments[$className] = $impact;
            
            if ($impact['risk_level'] === 'high') {
                $highImpactComponents++;
                
                $finding = new ConcreteFinding(
                    'ISSUE-' . str_pad($this->findingCounter++, 3, '0', STR_PAD_LEFT),
                    'high',
                    "High-impact component detected: {$className} (affects {$impact['affected_components']} components)",
                    'interdependency',
                    'high_impact_component'
                );
                
                $finding->addFileReference($serviceInfo['file'], 1, "Component: {$className}");
                $finding->addMetric('affected_components', $impact['affected_components'], 'components');
                $finding->addMetric('impact_score', $impact['impact_score'], 'score');
                $finding->addMetric('cascade_depth', $impact['cascade_depth'], 'levels');
                
                $finding->addEvidence('static_analysis', [
                    'component_name' => $className,
                    'impact_analysis' => $impact,
                    'dependent_components' => array_slice($impact['dependents'], 0, 10), // Limit for readability
                    'cascade_paths' => array_slice($impact['cascade_paths'], 0, 5)
                ], 'High-impact component change assessment');
                
                $finding->setRecommendation('Exercise extreme caution when modifying this component. Consider implementing feature flags and gradual rollout.');
                $finding->setEstimatedHours(8);
                
                $this->findings[] = $finding;
            }
        }
        
        // Store impact assessments for documentation
        $this->dependencyGraph['impact_assessments'] = $impactAssessments;
        
        Log::info("Found {$highImpactComponents} high-impact components");
    }

    /**
     * Calculate impact score for a component
     */
    private function calculateComponentImpact(string $className, array $serviceInfo): array
    {
        $dependents = $this->findDependentComponents($className);
        $cascadePaths = $this->traceCascadePaths($className, [], 0, 5); // Max depth 5
        
        $impactScore = $this->calculateImpactScore($dependents, $cascadePaths, $serviceInfo);
        
        $riskLevel = 'low';
        if ($impactScore > 8.0) {
            $riskLevel = 'critical';
        } elseif ($impactScore > 6.0) {
            $riskLevel = 'high';
        } elseif ($impactScore > 4.0) {
            $riskLevel = 'medium';
        }
        
        return [
            'component' => $className,
            'affected_components' => count($dependents),
            'cascade_depth' => $this->getMaxCascadeDepth($cascadePaths),
            'cascade_paths' => $cascadePaths,
            'dependents' => $dependents,
            'impact_score' => $impactScore,
            'risk_level' => $riskLevel,
            'change_recommendations' => $this->generateChangeRecommendations($riskLevel, $impactScore)
        ];
    }

    /**
     * Find components that depend on the given component
     */
    private function findDependentComponents(string $targetComponent): array
    {
        $dependents = [];
        
        foreach ($this->serviceMap as $className => $serviceInfo) {
            if ($className === $targetComponent) continue;
            
            if (in_array($targetComponent, $serviceInfo['dependencies']['classes'])) {
                $dependents[] = $className;
            }
        }
        
        return $dependents;
    }

    /**
     * Trace cascade paths from a component
     */
    private function traceCascadePaths(string $component, array $visited, int $depth, int $maxDepth): array
    {
        if ($depth >= $maxDepth || in_array($component, $visited)) {
            return [];
        }
        
        $visited[] = $component;
        $paths = [];
        
        $dependents = $this->findDependentComponents($component);
        
        foreach ($dependents as $dependent) {
            $subPaths = $this->traceCascadePaths($dependent, $visited, $depth + 1, $maxDepth);
            
            if (empty($subPaths)) {
                $paths[] = [$component, $dependent];
            } else {
                foreach ($subPaths as $subPath) {
                    $paths[] = array_merge([$component], $subPath);
                }
            }
        }
        
        return $paths;
    }

    /**
     * Calculate impact score based on dependents and cascade paths
     */
    private function calculateImpactScore(array $dependents, array $cascadePaths, array $serviceInfo): float
    {
        $score = 0.0;
        
        // Base score from number of dependents
        $score += min(5.0, count($dependents) * 0.5);
        
        // Score from cascade depth
        $maxDepth = $this->getMaxCascadeDepth($cascadePaths);
        $score += min(3.0, $maxDepth * 0.5);
        
        // Score from coupling
        $score += min(2.0, $serviceInfo['coupling_score'] * 0.25);
        
        // Bonus for critical paths (paths longer than 3 components)
        $criticalPaths = array_filter($cascadePaths, fn($path) => count($path) > 3);
        $score += min(2.0, count($criticalPaths) * 0.3);
        
        return round(min(10.0, $score), 2);
    }

    /**
     * Get maximum cascade depth from paths
     */
    private function getMaxCascadeDepth(array $cascadePaths): int
    {
        if (empty($cascadePaths)) {
            return 0;
        }
        
        return max(array_map('count', $cascadePaths));
    }

    /**
     * Generate change recommendations based on risk level
     */
    private function generateChangeRecommendations(string $riskLevel, float $impactScore): array
    {
        $recommendations = [];
        
        switch ($riskLevel) {
            case 'critical':
                $recommendations = [
                    'Implement comprehensive test coverage before any changes',
                    'Use feature flags for gradual rollout',
                    'Create detailed rollback plan',
                    'Coordinate with all dependent teams',
                    'Consider breaking changes into smaller increments',
                    'Implement monitoring and alerting for all dependent components'
                ];
                break;
                
            case 'high':
                $recommendations = [
                    'Ensure adequate test coverage',
                    'Implement feature flags',
                    'Create rollback plan',
                    'Notify dependent teams',
                    'Monitor dependent components post-deployment'
                ];
                break;
                
            case 'medium':
                $recommendations = [
                    'Verify test coverage',
                    'Consider feature flags',
                    'Monitor key dependent components'
                ];
                break;
                
            default:
                $recommendations = [
                    'Standard testing and deployment practices apply'
                ];
        }
        
        return $recommendations;
    }   
 /**
     * LETHAL: Generate Chain Trace Documentation with Visual Maps
     */
    public function generateChainTraceDocumentation(): void
    {
        Log::info('🔍 Generating Chain Trace Documentation');
        
        $documentation = $this->buildComprehensiveDocumentation();
        
        // Save documentation
        $this->saveDocumentation($documentation);
        
        // Generate additional visual formats
        $this->generateAdditionalVisualFormats();
        
        Log::info('Generated comprehensive chain trace documentation');
    }

    /**
     * Build comprehensive documentation
     */
    private function buildComprehensiveDocumentation(): array
    {
        return [
            'metadata' => [
                'generated_at' => now()->toISOString(),
                'total_services' => count($this->serviceMap),
                'total_dependencies' => array_sum(array_column($this->serviceMap, 'dependency_count')),
                'average_coupling_score' => round(array_sum($this->couplingScores) / max(1, count($this->couplingScores)), 2),
                'high_coupling_services' => count(array_filter($this->couplingScores, fn($score) => $score > 7.0))
            ],
            'event_cascades' => $this->dependencyChains['events'] ?? [],
            'service_dependencies' => $this->serviceMap,
            'coupling_analysis' => [
                'scores' => $this->couplingScores,
                'clusters' => $this->dependencyGraph['clusters']
            ],
            'dependency_graph' => $this->dependencyGraph,
            'impact_assessments' => $this->dependencyGraph['impact_assessments'] ?? [],
            'recommendations' => $this->generateGlobalRecommendations(),
            'visual_maps' => [
                'mermaid_available' => true,
                'dot_available' => true,
                'json_graph_available' => true
            ]
        ];
    }

    /**
     * Generate global recommendations
     */
    private function generateGlobalRecommendations(): array
    {
        $recommendations = [];
        
        $highCouplingCount = count(array_filter($this->couplingScores, fn($score) => $score > 7.0));
        $avgCouplingScore = count($this->couplingScores) > 0 ? array_sum($this->couplingScores) / count($this->couplingScores) : 0;
        
        if ($highCouplingCount > 5) {
            $recommendations[] = [
                'type' => 'architecture',
                'priority' => 'high',
                'description' => "High number of tightly coupled services ({$highCouplingCount})",
                'action' => 'Implement dependency injection and interface segregation',
                'estimated_effort' => '2-3 weeks'
            ];
        }
        
        if ($avgCouplingScore > 6.0) {
            $recommendations[] = [
                'type' => 'refactoring',
                'priority' => 'medium',
                'description' => "Overall coupling score is high ({$avgCouplingScore})",
                'action' => 'Systematic refactoring to reduce inter-service dependencies',
                'estimated_effort' => '3-4 weeks'
            ];
        }
        
        $complexCascades = array_filter($this->dependencyChains['events'] ?? [], 
            fn($cascade) => $this->calculateCascadeDepth($cascade) > 3);
            
        if (count($complexCascades) > 3) {
            $recommendations[] = [
                'type' => 'event_architecture',
                'priority' => 'medium',
                'description' => "Multiple complex event cascades detected",
                'action' => 'Simplify event chains and consider event sourcing patterns',
                'estimated_effort' => '1-2 weeks'
            ];
        }
        
        return $recommendations;
    }

    /**
     * Save documentation to files
     */
    private function saveDocumentation(array $documentation): void
    {
        $outputDir = storage_path('app/audit/interdependency-docs');
        
        if (!File::exists($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d-H-i-s');
        
        // Save JSON documentation
        $jsonFile = $outputDir . "/interdependency-analysis-{$timestamp}.json";
        File::put($jsonFile, json_encode($documentation, JSON_PRETTY_PRINT));
        
        // Save Markdown summary
        $markdownFile = $outputDir . "/interdependency-summary-{$timestamp}.md";
        File::put($markdownFile, $this->generateMarkdownSummary($documentation));
        
        Log::info("Saved documentation: {$jsonFile}, {$markdownFile}");
    }

    /**
     * Generate Markdown summary
     */
    private function generateMarkdownSummary(array $documentation): string
    {
        $md = "# Interdependency Chain Analysis Report\n\n";
        $md .= "Generated: " . $documentation['metadata']['generated_at'] . "\n\n";
        
        $md .= "## Summary\n\n";
        $md .= "- **Total Services**: " . $documentation['metadata']['total_services'] . "\n";
        $md .= "- **Total Dependencies**: " . $documentation['metadata']['total_dependencies'] . "\n";
        $md .= "- **Average Coupling Score**: " . $documentation['metadata']['average_coupling_score'] . "\n";
        $md .= "- **High Coupling Services**: " . $documentation['metadata']['high_coupling_services'] . "\n\n";
        
        $md .= "## Top Coupled Services\n\n";
        $topCoupled = array_slice($this->couplingScores, 0, 10, true);
        foreach ($topCoupled as $service => $score) {
            $md .= "- **{$service}**: {$score}\n";
        }
        
        $md .= "\n## Recommendations\n\n";
        foreach ($documentation['recommendations'] as $rec) {
            $md .= "### {$rec['type']} (Priority: {$rec['priority']})\n";
            $md .= "**Issue**: {$rec['description']}\n\n";
            $md .= "**Action**: {$rec['action']}\n\n";
            $md .= "**Estimated Effort**: {$rec['estimated_effort']}\n\n";
        }
        
        return $md;
    }

    /**
     * Generate additional visual formats
     */
    private function generateAdditionalVisualFormats(): void
    {
        // Generate JSON graph for D3.js or other visualization tools
        $jsonGraph = [
            'nodes' => $this->dependencyGraph['nodes'],
            'links' => array_map(function($edge) {
                return [
                    'source' => $edge['from'],
                    'target' => $edge['to'],
                    'type' => $edge['type']
                ];
            }, $this->dependencyGraph['edges'])
        ];
        
        $this->saveVisualMap('json', json_encode($jsonGraph, JSON_PRETTY_PRINT));
        
        // Generate Cytoscape.js format
        $cytoscapeData = [
            'elements' => array_merge(
                array_map(fn($node) => ['data' => $node], $this->dependencyGraph['nodes']),
                array_map(fn($edge) => ['data' => $edge], $this->dependencyGraph['edges'])
            )
        ];
        
        $this->saveVisualMap('cytoscape.json', json_encode($cytoscapeData, JSON_PRETTY_PRINT));
    }

    /**
     * Generate interdependency metrics for the phase result
     */
    private function generateInterdependencyMetrics(): array
    {
        return [
            'total_services_analyzed' => count($this->serviceMap),
            'total_dependencies' => array_sum(array_column($this->serviceMap, 'dependency_count')),
            'average_coupling_score' => round(array_sum($this->couplingScores) / max(1, count($this->couplingScores)), 2),
            'high_coupling_services' => count(array_filter($this->couplingScores, fn($score) => $score > 7.0)),
            'event_cascades_found' => count($this->dependencyChains['events'] ?? []),
            'complex_cascades' => count(array_filter($this->dependencyChains['events'] ?? [], 
                fn($cascade) => $this->calculateCascadeDepth($cascade) > 3)),
            'dependency_graph_nodes' => count($this->dependencyGraph['nodes']),
            'dependency_graph_edges' => count($this->dependencyGraph['edges']),
            'high_impact_components' => count(array_filter($this->dependencyGraph['impact_assessments'] ?? [], 
                fn($assessment) => $assessment['risk_level'] === 'high')),
            'total_findings' => count($this->findings)
        ];
    }
}