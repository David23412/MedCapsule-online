#!/bin/bash

# SSOT Legacy Read Verification Script
# Exits with non-zero status if any legacy identifiers remain

set -e

echo "🔍 SSOT Legacy Read Verification Script"
echo "======================================="

LEGACY_FOUND=0
TOTAL_CHECKS=0

# Function to check for legacy patterns
check_legacy_pattern() {
    local pattern="$1"
    local description="$2"
    local exclude_pattern="${3:-}"
    
    echo -n "Checking: $description... "
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    
    if [ -n "$exclude_pattern" ]; then
        matches=$(grep -r "$pattern" app/ resources/ --exclude-dir=node_modules --exclude="*.md" | grep -v "$exclude_pattern" | wc -l)
    else
        matches=$(grep -r "$pattern" app/ resources/ --exclude-dir=node_modules --exclude="*.md" | wc -l)
    fi
    
    if [ "$matches" -gt 0 ]; then
        echo "❌ FOUND $matches matches"
        LEGACY_FOUND=$((LEGACY_FOUND + 1))
        
        # Show first few matches for debugging
        echo "   Sample matches:"
        if [ -n "$exclude_pattern" ]; then
            grep -r "$pattern" app/ resources/ --exclude-dir=node_modules --exclude="*.md" | grep -v "$exclude_pattern" | head -3 | sed 's/^/   /'
        else
            grep -r "$pattern" app/ resources/ --exclude-dir=node_modules --exclude="*.md" | head -3 | sed 's/^/   /'
        fi
        echo ""
    else
        echo "✅ CLEAN"
    fi
}

echo "Scanning for legacy enrollment reads..."
check_legacy_pattern '\->is_enrolled' "Legacy is_enrolled field reads" "Support/State\|Observers\|SSOT\|->is_enrolled = \|enrollmentService->isEnrolled"
check_legacy_pattern '\$.*->enrolled_at' "Legacy enrolled_at field reads" "Support/State\|Observers\|SSOT"
check_legacy_pattern 'where.*is_enrolled' "Legacy is_enrolled queries" "Support/State\|Observers\|SSOT"

echo "Scanning for legacy assignment status reads..."
check_legacy_pattern '\->isOverdue\(\)' "Legacy isOverdue() method calls" "Support/State\|Observers\|SSOT\|statusService->isOverdue"
check_legacy_pattern '\->isActive\(\)' "Legacy isActive() method calls" "Support/State\|Observers\|SSOT\|statusService->isActive"
check_legacy_pattern '\->isExpired\(\)' "Legacy isExpired() method calls" "Support/State\|Observers\|SSOT\|statusService->isExpired"
check_legacy_pattern '\->getCurrentStatusAttribute\(\)' "Legacy getCurrentStatusAttribute() calls" "Support/State\|Observers\|SSOT"
check_legacy_pattern '\->getDisplayStatusAttribute\(\)' "Legacy getDisplayStatusAttribute() calls" "Support/State\|Observers\|SSOT"
check_legacy_pattern '\->status_cached' "Legacy status_cached field reads" "Support/State\|Observers\|SSOT"

echo "Scanning for legacy user progress reads..."
check_legacy_pattern '\->correct_answers_count' "Legacy correct_answers_count field reads" "Support/State\|Observers\|SSOT\|->correct_answers_count = \|UserProgressService"
check_legacy_pattern '\->total_questions_attempted' "Legacy total_questions_attempted field reads" "Support/State\|Observers\|SSOT\|->total_questions_attempted = \|UserProgressService"
check_legacy_pattern '\->xp' "Legacy xp field reads" "Support/State\|Observers\|SSOT\|->xp = \|UserProgressService"
check_legacy_pattern '\->level' "Legacy level field reads" "Support/State\|Observers\|SSOT\|->level = \|UserProgressService"
check_legacy_pattern '\->study_streak_days' "Legacy study_streak_days field reads" "Support/State\|Observers\|SSOT\|->study_streak_days = \|UserProgressService"

echo "Scanning for legacy mistake mastery reads..."
check_legacy_pattern '\->mastered' "Legacy mastered field reads" "Support/State\|Observers\|SSOT\|mastered_at\|mastered_topics_count"
check_legacy_pattern 'where.*mastered' "Legacy mastered queries" "Support/State\|Observers\|SSOT\|considered mastered"

echo "Scanning for legacy video progress reads..."
check_legacy_pattern '\->completed' "Legacy completed field reads" "Support/State\|Observers\|SSOT\|completed_at"
check_legacy_pattern 'where.*completed' "Legacy completed queries" "Support/State\|Observers\|SSOT\|status.*completed"

echo "Scanning for legacy topic lock reads..."
check_legacy_pattern '\->is_locked' "Legacy is_locked field reads" "Support/State\|Observers\|SSOT"
check_legacy_pattern 'where.*is_locked' "Legacy is_locked queries" "Support/State\|Observers\|SSOT\|ssot_is_locked"

echo ""
echo "======================================="
echo "VERIFICATION SUMMARY"
echo "======================================="
echo "Total checks performed: $TOTAL_CHECKS"
echo "Legacy patterns found: $LEGACY_FOUND"

if [ "$LEGACY_FOUND" -gt 0 ]; then
    echo ""
    echo "❌ VERIFICATION FAILED"
    echo "Legacy reads detected. Phase C migration incomplete."
    echo "Please replace all legacy reads with SSOT service calls."
    exit 1
else
    echo ""
    echo "✅ VERIFICATION PASSED"
    echo "No legacy reads detected. Ready for Phase D."
    exit 0
fi