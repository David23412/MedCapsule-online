<?php

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Models\User;
use App\Models\Topic;
use App\Models\Course;
use App\Models\Assignment;
use App\Http\Middleware\AssignmentSecurityMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AssignmentSecurityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private AssignmentSecurityMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AssignmentSecurityMiddleware();
    }

    /** @test */
    public function it_allows_student_to_access_their_own_assignment()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE
        ]);
        
        $request = Request::create('/assignments/' . $assignment->id, 'GET');
        $request->setRouteResolver(function () use ($assignment) {
            return new \Illuminate\Routing\Route(['GET'], '/assignments/{assignment}', []);
        });
        $request->route()->setParameter('assignment', $assignment->id);
        
        $this->actingAs($student);
        
        // Mock the next middleware
        $next = function ($request) {
            return response('OK');
        };
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    /** @test */
    public function it_allows_tutor_to_access_assignments_they_created()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE
        ]);
        
        $request = Request::create('/assignments/' . $assignment->id, 'GET');
        $request->setRouteResolver(function () use ($assignment) {
            return new \Illuminate\Routing\Route(['GET'], '/assignments/{assignment}', []);
        });
        $request->route()->setParameter('assignment', $assignment->id);
        
        $this->actingAs($tutor);
        
        // Mock the next middleware
        $next = function ($request) {
            return response('OK');
        };
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    /** @test */
    public function it_allows_admin_to_access_any_assignment()
    {
        // Arrange
        $admin = User::factory()->create(['role' => 'admin']);
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE
        ]);
        
        $request = Request::create('/assignments/' . $assignment->id, 'GET');
        $request->setRouteResolver(function () use ($assignment) {
            return new \Illuminate\Routing\Route(['GET'], '/assignments/{assignment}', []);
        });
        $request->route()->setParameter('assignment', $assignment->id);
        
        $this->actingAs($admin);
        
        // Mock the next middleware
        $next = function ($request) {
            return response('OK');
        };
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    /** @test */
    public function it_denies_access_to_other_students()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student1 = User::factory()->create(['role' => 'student']);
        $student2 = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student1->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE
        ]);
        
        $request = Request::create('/assignments/' . $assignment->id, 'GET');
        $request->setRouteResolver(function () use ($assignment) {
            return new \Illuminate\Routing\Route(['GET'], '/assignments/{assignment}', []);
        });
        $request->route()->setParameter('assignment', $assignment->id);
        
        $this->actingAs($student2);
        
        // Mock the next middleware
        $next = function ($request) {
            return response('OK');
        };
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('You do not have permission to access this assignment', $responseData['error']);
    }
    
    /** @test */
    public function it_denies_access_to_locked_assignments()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_LOCKED
        ]);
        
        $request = Request::create('/assignments/' . $assignment->id, 'GET');
        $request->setRouteResolver(function () use ($assignment) {
            return new \Illuminate\Routing\Route(['GET'], '/assignments/{assignment}', []);
        });
        $request->route()->setParameter('assignment', $assignment->id);
        
        $this->actingAs($student);
        
        // Mock the next middleware
        $next = function ($request) {
            return response('OK');
        };
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('This assignment is locked', $responseData['error']);
    }
    
    /** @test */
    public function it_denies_access_when_topic_is_locked()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create([
            'course_id' => $course->id,
            'is_locked' => true,
            'locked_by_tutor_id' => $tutor->id,
            'lock_reason' => 'Topic is under maintenance'
        ]);
        
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE
        ]);
        
        $request = Request::create('/assignments/' . $assignment->id, 'GET');
        $request->setRouteResolver(function () use ($assignment) {
            return new \Illuminate\Routing\Route(['GET'], '/assignments/{assignment}', []);
        });
        $request->route()->setParameter('assignment', $assignment->id);
        
        $this->actingAs($student);
        
        // Mock the next middleware
        $next = function ($request) {
            return response('OK');
        };
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Topic is under maintenance', $responseData['error']);
    }
    
    /** @test */
    public function it_allows_tutors_with_student_relationship()
    {
        // Arrange
        $tutor1 = User::factory()->create(['role' => 'tutor']);
        $tutor2 = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor1->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE
        ]);
        
        // Create tutor-student relationship for tutor2
        DB::table('tutor_student_relationships')->insert([
            'tutor_id' => $tutor2->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $request = Request::create('/assignments/' . $assignment->id, 'GET');
        $request->setRouteResolver(function () use ($assignment) {
            return new \Illuminate\Routing\Route(['GET'], '/assignments/{assignment}', []);
        });
        $request->route()->setParameter('assignment', $assignment->id);
        
        $this->actingAs($tutor2);
        
        // Mock the next middleware
        $next = function ($request) {
            return response('OK');
        };
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    /** @test */
    public function it_handles_assignment_id_from_input()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE
        ]);
        
        $request = Request::create('/assignments/submit', 'POST', ['assignment_id' => $assignment->id]);
        
        $this->actingAs($student);
        
        // Mock the next middleware
        $next = function ($request) {
            return response('OK');
        };
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    /** @test */
    public function it_returns_not_found_for_nonexistent_assignment()
    {
        // Arrange
        $student = User::factory()->create(['role' => 'student']);
        
        $request = Request::create('/assignments/999999', 'GET');
        $request->setRouteResolver(function () {
            return new \Illuminate\Routing\Route(['GET'], '/assignments/{assignment}', []);
        });
        $request->route()->setParameter('assignment', 999999);
        
        $this->actingAs($student);
        
        // Mock the next middleware
        $next = function ($request) {
            return response('OK');
        };
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Assignment not found', $responseData['error']);
    }
    
    /** @test */
    public function it_requires_authentication()
    {
        // Arrange
        $tutor = User::factory()->create(['role' => 'tutor']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create();
        $topic = Topic::factory()->create(['course_id' => $course->id]);
        
        $assignment = Assignment::factory()->create([
            'tutor_id' => $tutor->id,
            'student_id' => $student->id,
            'topic_id' => $topic->id,
            'course_id' => $course->id,
            'status' => Assignment::STATUS_ACTIVE
        ]);
        
        $request = Request::create('/assignments/' . $assignment->id, 'GET');
        $request->setRouteResolver(function () use ($assignment) {
            return new \Illuminate\Routing\Route(['GET'], '/assignments/{assignment}', []);
        });
        $request->route()->setParameter('assignment', $assignment->id);
        
        // No authentication
        
        // Mock the next middleware
        $next = function ($request) {
            return response('OK');
        };
        
        // Act
        $response = $this->middleware->handle($request, $next);
        
        // Assert
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Authentication required', $responseData['error']);
    }
}