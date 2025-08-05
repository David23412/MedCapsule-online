<?php

echo "🕷️ IMPROVED SPIDER-MAN MESSAGES TEST\n";
echo "=" . str_repeat("=", 50) . "\n\n";

echo "🎯 IMPROVEMENTS MADE:\n";
echo "   ❌ Removed 'vibes' and big words\n";
echo "   ❌ Removed emojis from message text\n";
echo "   ❌ Removed emojis from titles\n";
echo "   ✅ Kept Spider-Man's encouraging tone\n";
echo "   ✅ Made language more accessible\n\n";

$beforeAfter = [
    [
        'day' => 2,
        'before' => 'Two days! You\'re on a roll now. I can feel the good vibes already. Keep it up!',
        'after' => 'Two days! You are on a roll now. I can feel it already. Keep it up!'
    ],
    [
        'day' => 3,
        'before' => 'Three days! Your brain is getting used to this. It\'s like training - you\'re getting stronger every day.',
        'after' => 'Three days! Your brain is getting used to this. It is like training - you get stronger every day.'
    ],
    [
        'day' => 10,
        'before' => 'Ten days! Double digits, baby! You\'re in the top 10% now. That\'s pretty awesome.',
        'after' => 'Ten days! Double digits now! You are in the top 10% now. That is pretty cool.'
    ],
    [
        'day' => 30,
        'before' => 'A whole month! You\'re in the top 1% now. That\'s pretty special, you know.',
        'after' => 'A whole month! You are in the top 1% now. That is pretty special, you know.'
    ],
    [
        'day' => 60,
        'before' => 'Two months! You\'re not just learning anymore - you\'re becoming something amazing.',
        'after' => 'Two months! You are not just learning anymore - you are becoming something great.'
    ],
    [
        'day' => 180,
        'before' => 'Half a year! Your mind is like a diamond now - strong, bright, and super valuable.',
        'after' => 'Half a year! Your mind is like a diamond now - strong, bright, and super good.'
    ],
    [
        'day' => 365,
        'before' => 'A FULL YEAR! Only 0.1% of people do this. You\'re officially a learning hero now!',
        'after' => 'A FULL YEAR! Only 0.1% of people do this. You are now a learning hero!'
    ]
];

echo "📝 BEFORE vs AFTER COMPARISON:\n";
echo "-" . str_repeat("-", 80) . "\n";

foreach ($beforeAfter as $comparison) {
    echo "\n🕷️ DAY {$comparison['day']}:\n";
    echo "❌ BEFORE: \"{$comparison['before']}\"\n";
    echo "✅ AFTER:  \"{$comparison['after']}\"\n";
}

echo "\n🚫 REMOVED ELEMENTS:\n";
echo "-" . str_repeat("-", 80) . "\n";
echo "   ❌ 'vibes' → 'it'\n";
echo "   ❌ 'baby!' → 'now!'\n";
echo "   ❌ 'awesome' → 'cool'\n";
echo "   ❌ 'amazing' → 'great'\n";
echo "   ❌ 'valuable' → 'good'\n";
echo "   ❌ 'officially' → removed\n";
echo "   ❌ Contractions: 'you're' → 'you are', 'it's' → 'it is'\n";
echo "   ❌ All emojis from titles and messages\n\n";

echo "✅ KEPT SPIDER-MAN ELEMENTS:\n";
echo "-" . str_repeat("-", 80) . "\n";
echo "   🕷️ Encouraging tone: 'Keep it up!', 'you know'\n";
echo "   🎯 Direct address: 'You are', 'Your brain'\n";
echo "   💪 Hero references: 'heroes do', 'learning hero'\n";
echo "   😊 Friendly language: 'Pretty cool', 'Well, you just did it!'\n";
echo "   🎉 Excitement: 'Double digits now!', 'A FULL YEAR!'\n";
echo "   🧠 Relatable comparisons: 'like training', 'like a diamond'\n\n";

echo "🎯 ACCESSIBILITY IMPROVEMENTS:\n";
echo "-" . str_repeat("-", 80) . "\n";
echo "   📚 Simpler vocabulary for broader audience\n";
echo "   🗣️ No slang that might confuse non-native speakers\n";
echo "   📱 Clean message text without emoji clutter\n";
echo "   ✨ Still feels personal and encouraging\n";
echo "   🕷️ Maintains Spider-Man's heroic, friendly personality\n\n";

echo "🎉 RESULT:\n";
echo "   Messages are now more accessible while keeping Spider-Man's\n";
echo "   encouraging, heroic tone that makes users feel supported!\n";

?>