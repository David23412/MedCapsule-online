<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->foreignId('topic_id')->constrained()->onDelete('cascade'); // Foreign key to topics table
            $table->text('question'); // Question text
            $table->string('source')->nullable(); // Source of the question (e.g., 'Assuit', 'Cairo', etc.)

            // Add multiple-choice options - nullable for written questions
            $table->string('option_a')->nullable();
            $table->string('option_b')->nullable();
            $table->string('option_c')->nullable();
            $table->string('option_d')->nullable();
            
            // Store correct answer (e.g., 'A', 'B', 'C', 'D' for multiple choice, or full text for written)
            $table->text('correct_answer');

            // Add question_type field and default existing questions to 'multiple_choice'
            $table->string('question_type')->default('multiple_choice');

            $table->text('explanation')->nullable(); // Optional explanation shown after answering
            $table->string('image_url')->nullable(); // Optional image for the question
            
            // Add extra_topic field as string to natively support comma-separated "1,2,3" topic injections!
            $table->string('extra_topic')->nullable()->comment('Comma separated topic IDs, e.g., 1,2,3');
            
            // Add frequency count for Golden Standard recurrence tracking
            $table->integer('exam_appearances_count')->default(0);
            
            // Add field for alternative correct answers (TEXT field to store plain text)
            $table->text('alternative_answers')->nullable();

            // Lightweight concept tagging lives directly on the question.
            $table->string('concept')->nullable();

            $table->timestamps(); // Created_at and Updated_at timestamps
            
            // Indexes for better performance
            $table->index('topic_id'); // Fast look-up for questions by topic
            $table->index('extra_topic'); // Fast look-up for questions by extra topic
            $table->index(['topic_id', 'source'], 'idx_questions_topic_source'); // Latest-source lookups per topic
            $table->index(['source', 'question_type']); // For filtering questions by source and type
            $table->index(['topic_id', 'question_type']); // For filtering questions by topic and type
            $table->index(['topic_id', 'concept']); // For concept-aware retrieval
            $table->index(['extra_topic', 'question_type']); // For filtering extra topic questions by type
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            try {
                DB::unprepared('
                    CREATE TRIGGER trg_questions_exam_count_insert
                    BEFORE INSERT ON questions
                    FOR EACH ROW
                    BEGIN
                        IF NEW.extra_topic IS NULL OR NEW.extra_topic = "" THEN
                            SET NEW.exam_appearances_count = 0;
                        ELSE
                            SET NEW.exam_appearances_count = (LENGTH(REPLACE(NEW.extra_topic, " ", "")) - LENGTH(REPLACE(REPLACE(NEW.extra_topic, " ", ""), ",", "")) + 1);
                        END IF;
                    END;
                ');

                DB::unprepared('
                    CREATE TRIGGER trg_questions_exam_count_update
                    BEFORE UPDATE ON questions
                    FOR EACH ROW
                    BEGIN
                        IF NEW.extra_topic IS NULL OR NEW.extra_topic = "" THEN
                            SET NEW.exam_appearances_count = 0;
                        ELSE
                            SET NEW.exam_appearances_count = (LENGTH(REPLACE(NEW.extra_topic, " ", "")) - LENGTH(REPLACE(REPLACE(NEW.extra_topic, " ", ""), ",", "")) + 1);
                        END IF;
                    END;
                ');
            } catch (\Throwable $e) {
                Log::warning('Skipping questions exam-count triggers; database user cannot create triggers.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_questions_exam_count_insert');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_questions_exam_count_update');
        }
        Schema::dropIfExists('questions');
    }
};
