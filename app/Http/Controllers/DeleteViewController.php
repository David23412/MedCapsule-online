<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Psr\Log\LoggerInterface;

class DeleteViewController extends Controller
{
    private ViewFactory $viewFactory;
    private LoggerInterface $logger;

    public function __construct(ViewFactory $viewFactory, LoggerInterface $logger)
    {
        $this->middleware('auth');
        $this->viewFactory = $viewFactory;
        $this->logger = $logger;
    }

    public function show(Request $request, string $resourceType, $resourceId)
    {
        try {
            $viewName = "control.delete.confirm.{$resourceType}";

            if (!$this->viewFactory->exists($viewName)) {
                abort(404, "Deletion confirmation view for resource type '{$resourceType}' not found.");
            }

            return $this->viewFactory->make($viewName, [
                'resourceType' => $resourceType,
                'resourceId' => $resourceId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to show deletion confirmation view', [
                'resourceType' => $resourceType,
                'resourceId' => $resourceId,
                'error' => $e->getMessage(),
            ]);

            return $this->viewFactory->make('errors.500');
        }
    }
}
