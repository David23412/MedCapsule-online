<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteTemplateCoverageTest extends TestCase
{
    public function test_registered_controller_actions_reference_existing_templates(): void
    {
        $missing = [];
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (!str_contains($action, '@')) continue;
            [$class, $method] = explode('@', $action, 2);
            if (!class_exists($class) || !method_exists($class, $method)) continue;
            $reflection = new \ReflectionMethod($class, $method);
            $lines = file($reflection->getFileName());
            $source = implode('', array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
            preg_match_all('/view\(\s*[\'"]([^\'"]+)[\'"]\s*[,\)]/', $source, $matches);
            foreach (array_unique($matches[1]) as $view) {
                if (!view()->exists($view)) $missing[] = $route->uri().' → '.$view;
            }
        }
        $this->assertSame([], $missing, 'Routed pages must have templates: '.implode(', ', $missing));
    }
}
