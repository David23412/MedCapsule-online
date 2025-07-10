            // Get fresh questions from stored IDs
            $questionIds = session('tutor_questions', []);
            $questions = Question::whereIn('id', $questionIds)->get();

            return view('quiz.tutor_mode', [
                'questions' => $questions,
                'topic' => $topic,
                'sources' => $sources,
                'total_questions' => $questions->count()
            ]); 