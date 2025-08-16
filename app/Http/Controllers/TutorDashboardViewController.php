<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TutorDashboardService;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\Auth\Factory as Auth;
use Psr\Log\LoggerInterface;

class TutorDashboardViewController extends Controller
{
    private TutorDashboardService $dashboardService;
    private ViewFactory $viewFactory;
    private Auth $auth;
    private LoggerInterface $logger;

    public function __construct(
        TutorDashboardService $dashboardService,
        ViewFactory $viewFactory,
        Auth $auth,
        LoggerInterface $logger
    ) {
        $this->middleware('auth');
        $this->middleware('role:tutor');
        $this->dashboardService = $dashboardService;
        $this->viewFactory = $viewFactory;
        $this->auth = $auth;
        $this->logger = $logger;
    }

    public function index(Request $request)
    {
        try {
            $tutorId = $this->auth->id();
            $dashboardData = $this->dashboardService->getDashboardData($tutorId);

            return $this->viewFactory->make('tutor.dashboard', $dashboardData);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load tutor dashboard', [
                'tutor_id' => $this->auth->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->viewFactory->make('errors.500');
        }
    }
}
