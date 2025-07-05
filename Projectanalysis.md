# Project Analysis

This document provides an analysis of the MedCapsule project, including its database schema, models, and views.

## Database Schema Analysis

The database schema consists of the following tables:

*   **users**: Stores user information, including authentication details, profile data, and learning progress metrics (correct answers, total questions attempted, XP, study streak).
*   **password_reset_tokens**: Stores tokens for password reset functionality.
*   **sessions**: Stores session data for user activity tracking.
*   **courses**: Stores course information, including name, description, image, color, and semester.
*   **topics**: Stores topic information, including name, course association, and display order.
*   **questions**: Stores question data, including question text, options, correct answer, topic association, and image URL.
*   **mistakes**: Stores user-specific mistake data, including question association, submitted answer, and attempt history.
*   **course_user**: Stores the many-to-many relationship between courses and users, including enrollment status.
*   **quiz_attempts**: Stores quiz attempt data, including user, topic, duration, grade, and correct answers.
*   **user_topic_progress**: Stores user-specific progress data for each topic, including percentage grade and last attempt date.
*   **notifications**: Stores notifications for users, including type, title, message, data, and read status.

## Model Analysis

The `app/Models` directory contains Eloquent models representing the database tables. Key models include:

*   **User**: Represents a user in the system. Includes relationships to courses, topic progress, quiz attempts, and notifications. Methods for enrolling/unenrolling in courses and checking course access are present.
*   **Course**: Represents a course. Includes relationships to topics and users. Methods for retrieving enrolled students and calculating completion percentage are present. Caching is implemented to improve performance.
*   **Topic**: Represents a topic within a course. Includes relationships to questions and user progress.
*   **Question**: Represents a question within a topic.
*   **Mistake**: Represents a user's mistake on a specific question.
*   **QuizAttempt**: Represents a user's attempt at a quiz.
*   **UserTopicProgress**: Represents a user's progress on a specific topic.
*   **Notification**: Represents a notification for a user.

## View Analysis

The `resources/views` directory contains Blade templates for rendering the user interface. Key views include:

*   **admin/manage-access.blade.php**: Provides an interface for managing user access to courses and promoting users to admin roles.
*   **auth/\***: Contains templates for authentication-related pages (login, register, reset password, etc.).
*   **components/\***: Contains reusable Blade components, such as `live-user-counter` and `notification-bell`.
*   **courses.blade.php**: Displays a list of available courses.
*   **home.blade.php**: Displays the home page with featured courses and marketing information.
*   **mistakes.blade.php**: Displays a list of the user's mistakes.
*   **profile.blade.php**: Displays the user's profile information, learning progress, and leaderboard ranking.
*   **settings.blade.php**: Displays the user's settings page.
*   **solve\_quiz.blade.php**: Displays the quiz interface for a specific topic.
*   **topics\_for\_course.blade.php**: Displays a list of topics for a specific course.
*   **quiz/random\_quiz.blade.php**: Displays a random quiz generated from selected courses.

The views utilize Bootstrap for styling and layout, and Font Awesome for icons. JavaScript is used for interactive elements and data fetching.

## General Observations

*   The project follows a standard Laravel architecture with a clear separation of concerns between models, views, and controllers.
*   Database migrations are used to manage the database schema.
*   Eloquent models are used to interact with the database.
*   Blade templates are used to render the user interface.
*   Caching is implemented to improve performance.
*   Middleware is used to protect routes and enforce access control.
*   The project includes features for course management, quiz-based assessment, progress tracking, and personalized learning paths.
