<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuestionRequest;
use App\Services\QuestionService;
use App\Services\TopicService;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;

class QuestionController extends Controller
{
    private QuestionService $questionService;
    private TopicService $topicService;
    private LoggerInterface $logger;
    private ResponseFactory $responseFactory;
    private ViewFactory $viewFactory;

    public function __construct(
        QuestionService $questionService,
        TopicService $topicService,
        LoggerInterface $logger,
        ResponseFactory $responseFactory,
        ViewFactory $viewFactory
    ) {
        $this->middleware(['auth', 'admin']);
        $this->questionService = $questionService;
        $this->topicService = $topicService;
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->viewFactory = $viewFactory;
    }

    public function showAddQuestionForm()
    {
        try {
            $topics = $this->topicService->getAllTopicsWithCourses();
            return $this->viewFactory->make('add_question', compact('topics'));
        } catch (\Exception $e) {
            $this->logger->error('Failed to show add question form', ['error' => $e->getMessage()]);
            return $this->responseFactory->redirectToRoute('admin.dashboard')->with('error', 'Could not load the form.');
        }
    }

    public function storeQuestion(QuestionRequest $request)
    {
        try {
            $this->questionService->createQuestion($request->validated());
            return $this->responseFactory->redirectToRoute('questions.add')->with('success', 'Question added successfully!');
        } catch (\Exception $e) {
            $this->logger->error('Failed to store question', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to add question. Please try again.');
        }
    }

    public function editQuestion($id)
    {
        try {
            $question = $this->questionService->getQuestionById($id);
            $topics = $this->topicService->getAllTopicsWithCourses();
            return $this->viewFactory->make('edit_question', compact('question', 'topics'));
        } catch (\Exception $e) {
            $this->logger->error('Failed to show edit question form', ['error' => $e->getMessage()]);
            return $this->responseFactory->redirectToRoute('admin.questions')->with('error', 'Could not load the form.');
        }
    }

    public function updateQuestion(QuestionRequest $request, $id)
    {
        try {
            $this->questionService->updateQuestion($id, $request->validated());
            return $this->responseFactory->redirectToRoute('questions.edit', $id)->with('success', 'Question updated successfully!');
        } catch (\Exception $e) {
            $this->logger->error('Failed to update question', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update question. Please try again.');
        }
    }

    public function deleteQuestion($id)
    {
        try {
            $this->questionService->deleteQuestion($id);
            return $this->responseFactory->redirectToRoute('admin.questions')->with('success', 'Question deleted successfully!');
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete question', ['error' => $e->getMessage()]);
            return back()->with('error', 'Failed to delete question. Please try again.');
        }
    }
}
