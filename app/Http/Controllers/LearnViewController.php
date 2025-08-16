<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LearnService;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\Auth\Factory as Auth;
use Psr\Log\LoggerInterface;

class LearnViewController extends Controller
{
    private LearnService $learnService;
    private ViewFactory $viewFactory;
    private Auth $auth;
    private LoggerInterface $logger;

    public function __construct(
        LearnService $learnService,
        ViewFactory $viewFactory,
        Auth $auth,
        LoggerInterface $logger
    ) {
        $this->middleware('auth');
        $this->learnService = $learnService;
        $this->viewFactory = $viewFactory;
        $this->auth = $auth;
        $this->logger = $logger;
    }

    public function index(Request $request)
    {
        try {
            $userId = $this->auth->id();
            $learnData = $this->learnService->getLearnData($userId);

            return $this->viewFactory->make('learn.index', $learnData);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load learn page', [
                'user_id' => $this->auth->id(),
                'error' => $e->getMessage(),
            ]);

            return $this->viewFactory->make('errors.500');
        }
    }
}
