<?php

echo "🕷️ SPIDER-MAN STYLE STREAK MESSAGES TEST\n";
echo "=" . str_repeat("=", 50) . "\n\n";

echo "🎯 NEW TONE: Quirky, Fun, Encouraging - Like Spider-Man!\n\n";

$beforeAfter = [
    [
        'day' => 1,
        'before' => 'Congratulations on completing your first quiz! Every expert was once a beginner. You\'ve taken the most important step.',
        'after' => 'Nice! Your first quiz is done. Everyone starts somewhere, and you just started. Pretty cool, right?'
    ],
    [
        'day' => 2,
        'before' => 'Two days in a row! You\'re already building a powerful learning habit. Consistency is the key to mastery.',
        'after' => 'Two days! You\'re on a roll now. I can feel the good vibes already. Keep it up!'
    ],
    [
        'day' => 3,
        'before' => 'Three days strong! Your brain is starting to crave this daily learning. You\'re rewiring yourself for success.',
        'after' => 'Three days! Your brain is getting used to this. It\'s like training - you\'re getting stronger every day.'
    ],
    [
        'day' => 7,
        'before' => 'A full week of dedication! You\'ve proven to yourself that you can commit. This is where champions are made.',
        'after' => 'A whole week! You showed up every day. That\'s what heroes do - they show up.'
    ],
    [
        'day' => 10,
        'before' => 'Ten days of unstoppable learning! You\'re in the top 10% of learners. Your dedication is truly inspiring.',
        'after' => 'Ten days! Double digits, baby! You\'re in the top 10% now. That\'s pretty awesome.'
    ],
    [
        'day' => 21,
        'before' => 'Twenty-one days! Scientists say it takes 21 days to form a habit. You\'ve officially rewired your brain for success!',
        'after' => 'Twenty-one days! They say it takes 21 days to make a habit. Well, you just did it!'
    ],
    [
        'day' => 30,
        'before' => 'A full month of dedication! You\'ve joined the elite 1% of learners. Your commitment is extraordinary!',
        'after' => 'A whole month! You\'re in the top 1% now. That\'s pretty special, you know.'
    ],
    [
        'day' => 365,
        'before' => 'A FULL YEAR! You\'ve achieved what less than 0.1% of people ever do. You are now immortal in the halls of learning greatness!',
        'after' => 'A FULL YEAR! Only 0.1% of people do this. You\'re officially a learning hero now!'
    ]
];

echo "📝 BEFORE vs AFTER COMPARISON:\n";
echo "-" . str_repeat("-", 80) . "\n";

foreach ($beforeAfter as $comparison) {
    echo "\n🕷️ DAY {$comparison['day']}:\n";
    echo "❌ BEFORE (AI-like): \"{$comparison['before']}\"\n";
    echo "✅ AFTER (Spider-Man): \"{$comparison['after']}\"\n";
}

echo "\n🎨 SPIDER-MAN TONE CHARACTERISTICS:\n";
echo "-" . str_repeat("-", 80) . "\n";
echo "   🕷️ Casual Language: 'Nice!', 'Pretty cool, right?', 'baby!'\n";
echo "   🎯 Direct & Simple: Short sentences, no big words\n";
echo "   🔥 Encouraging: 'Keep it up!', 'You\'re awesome', 'That\'s amazing'\n";
echo "   💪 Hero References: 'That\'s what heroes do', 'learning hero'\n";
echo "   😊 Friendly Tone: 'you know', 'Well, you just did it!'\n";
echo "   ⚡ Energy & Excitement: 'Double digits, baby!', 'good vibes'\n";
echo "   🧠 Relatable Comparisons: 'It\'s like training', 'getting stronger'\n";
echo "   🎉 Celebration: 'Pretty awesome', 'That\'s pretty special'\n\n";

echo "🚫 REMOVED AI-LIKE ELEMENTS:\n";
echo "-" . str_repeat("-", 80) . "\n";
echo "   ❌ Formal language: 'Congratulations', 'extraordinary'\n";
echo "   ❌ Long explanations: 'powerful learning habit', 'key to mastery'\n";
echo "   ❌ Corporate speak: 'dedication', 'commitment', 'unwavering'\n";
echo "   ❌ Overly dramatic: 'immortal', 'halls of greatness'\n";
echo "   ❌ Complex words: 'phenomenal', 'transformation', 'inspiring'\n";
echo "   ❌ Robotic phrases: 'You have achieved', 'You have joined'\n\n";

echo "✨ HUMAN & ALIVE FEATURES:\n";
echo "-" . str_repeat("-", 80) . "\n";
echo "   🗣️ Conversational: Sounds like talking to a friend\n";
echo "   😄 Playful: 'baby!', 'Pretty cool, right?'\n";
echo "   🤝 Personal: 'I can feel the good vibes'\n";
echo "   🎯 Relatable: Uses everyday language and comparisons\n";
echo "   💫 Encouraging: Builds confidence without being fake\n";
echo "   🕷️ Character: Has Spider-Man's quirky, heroic personality\n\n";

echo "🎉 RESULT:\n";
echo "   Messages now feel like a friendly superhero is cheering you on,\n";
echo "   not like an AI trying to sound motivational!\n";

?>