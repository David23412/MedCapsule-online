# Simple Guide to Bulk Add Pictures to Questions

## Overview
This guide shows the easiest way to add pictures to your questions in a CSV file so they appear on your website. It uses your Laravel setup for quick results.

## Step 1: Prepare Your Pictures
1. Collect all your pictures in one folder on your computer.
2. Name them simply, like `question1.jpg`, `question2.jpg`, etc.
3. Make sure names match your question numbers.

## Step 2: Upload Pictures to Website
1. Create a folder in your project: `storage/app/public/images/questions/`
2. Run this command in your terminal: `php artisan storage:link`
3. Create a file called `bulk-upload.php` in your `scripts/` folder with this code:

```php
<?php
$images = glob('/path/to/your/images/*.jpg'); // Change to your folder path
foreach ($images as $image) {
    $filename = basename($image);
    Storage::disk('public')->put("images/questions/$filename", file_get_contents($image));
    echo "Uploaded: $filename\n";
}
```

4. Run it: `php scripts/bulk-upload.php`

## Step 3: Create CSV File
Make a CSV file called `questions.csv` in `storage/app/` with columns:
- question_text
- answer
- image_path

Example:
```
question_text,answer,image_path
What is 2+2?,4,images/questions/question1.jpg
How does it work?,Like this,images/questions/question2.jpg
```

## Step 4: Import to Database
1. Make sure your questions table has an `image_path` column.
2. Create `database/seeders/QuestionsSeeder.php`:

```php
<?php
namespace Database\Seeders;
use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionsSeeder extends Seeder {
    public function run() {
        $csvFile = storage_path('app/questions.csv');
        $rows = array_map('str_getcsv', file($csvFile));
        $header = array_shift($rows);
        foreach ($rows as $row) {
            $data = array_combine($header, $row);
            Question::create($data);
        }
    }
}
```

3. Run: `php artisan db:seed --class=QuestionsSeeder`

## Step 5: Show on Website
In your Blade view, add:
```php
@foreach($questions as $question)
    <h3>{{ $question->question_text }}</h3>
    @if($question->image_path)
        <img src="{{ asset($question->image_path) }}" alt="Image">
    @endif
    <p>{{ $question->answer }}</p>
@endforeach
```

## Done!
Your pictures will now show with questions. This method is fast and uses your existing Laravel tools.
