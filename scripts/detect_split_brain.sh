#!/bin/bash

# 🧠💥 SPLIT-BRAIN LOGIC DETECTOR
# Hunts down dual truth sources and boolean soup with EXTREME PREJUDICE

set -e

echo "🔥 SPLIT-BRAIN TERMINATOR ACTIVATED"
echo "======================================="

REPORT_FILE="SPLIT_BRAIN_VIOLATIONS_$(date +%Y%m%d_%H%M%S).md"

echo "# 🧠💥 SPLIT-BRAIN LOGIC VIOLATIONS REPORT" > $REPORT_FILE
echo "**Generated**: $(date)" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Phase 1: Database Schema Analysis
echo "🔍 Phase 1: Scanning database schema for split-brain patterns..."
echo "## 🚨 DATABASE SCHEMA VIOLATIONS" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Look for boolean + timestamp pairs
echo "### Boolean + Timestamp Redundancy" >> $REPORT_FILE
grep -r "boolean.*confirmed\|confirmed.*boolean" database/migrations/ | head -10 >> $REPORT_FILE || true
grep -r "boolean.*active\|active.*boolean" database/migrations/ | head -10 >> $REPORT_FILE || true
grep -r "boolean.*published\|published.*boolean" database/migrations/ | head -10 >> $REPORT_FILE || true
grep -r "boolean.*completed\|completed.*boolean" database/migrations/ | head -10 >> $REPORT_FILE || true

# Look for boolean soup (4+ booleans in same migration)
echo "" >> $REPORT_FILE
echo "### Boolean Soup Detection" >> $REPORT_FILE
for file in database/migrations/*.php; do
    boolean_count=$(grep -c "boolean(" "$file" 2>/dev/null || echo 0)
    if [ "$boolean_count" -ge 4 ]; then
        echo "🔥 **BOOLEAN SOUP**: $file ($boolean_count booleans)" >> $REPORT_FILE
        grep -n "boolean(" "$file" >> $REPORT_FILE
        echo "" >> $REPORT_FILE
    fi
done

# Phase 2: Model Analysis
echo "🔍 Phase 2: Scanning models for split-brain patterns..."
echo "## 🚨 MODEL VIOLATIONS" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Look for models with suspicious boolean clusters
echo "### Models with Boolean Clusters" >> $REPORT_FILE
for file in app/Models/*.php; do
    boolean_count=$(grep -c "boolean\|bool" "$file" 2>/dev/null || echo 0)
    if [ "$boolean_count" -ge 3 ]; then
        echo "🔥 **BOOLEAN CLUSTER**: $file ($boolean_count boolean references)" >> $REPORT_FILE
        grep -n "boolean\|bool\|true\|false" "$file" | head -10 >> $REPORT_FILE
        echo "" >> $REPORT_FILE
    fi
done

# Phase 3: Controller Logic Analysis  
echo "🔍 Phase 3: Scanning controllers for split-brain queries..."
echo "## 🚨 CONTROLLER VIOLATIONS" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Look for split-brain conditional logic
echo "### Split-Brain Conditional Logic" >> $REPORT_FILE
grep -r "is_confirmed\|confirmed_at\|confirmation_status" app/Http/Controllers/ | head -20 >> $REPORT_FILE || true
grep -r "is_active\|active_at\|activation_status" app/Http/Controllers/ | head -20 >> $REPORT_FILE || true
grep -r "is_published\|published_at\|publication_status" app/Http/Controllers/ | head -20 >> $REPORT_FILE || true

# Phase 4: Service Analysis
echo "🔍 Phase 4: Scanning services for boolean soup..."
echo "## 🚨 SERVICE VIOLATIONS" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Look for boolean soup in services
for file in app/Services/*.php; do
    if [ -f "$file" ]; then
        boolean_checks=$(grep -c "is_.*\|->.*_at" "$file" 2>/dev/null || echo 0)
        if [ "$boolean_checks" -ge 5 ]; then
            echo "🔥 **SERVICE BOOLEAN SOUP**: $file ($boolean_checks boolean checks)" >> $REPORT_FILE
            grep -n "is_.*\|->.*_at" "$file" | head -10 >> $REPORT_FILE
            echo "" >> $REPORT_FILE
        fi
    fi
done

# Phase 5: View Analysis
echo "🔍 Phase 5: Scanning views for split-brain display logic..."
echo "## 🚨 VIEW VIOLATIONS" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Look for split-brain logic in views
echo "### Split-Brain Display Logic" >> $REPORT_FILE
grep -r "is_confirmed\|confirmed_at" resources/views/ | head -10 >> $REPORT_FILE || true
grep -r "is_active\|active_at" resources/views/ | head -10 >> $REPORT_FILE || true

# Phase 6: Specific Anti-Patterns
echo "🔍 Phase 6: Hunting specific anti-patterns..."
echo "## 🚨 SPECIFIC ANTI-PATTERNS" >> $REPORT_FILE
echo "" >> $REPORT_FILE

# Authentication split-brain
echo "### Authentication Split-Brain" >> $REPORT_FILE
grep -r "email_verified_at.*is_verified\|is_verified.*email_verified_at" app/ >> $REPORT_FILE || true

# Enrollment split-brain  
echo "### Enrollment Split-Brain" >> $REPORT_FILE
grep -r "enrolled_at.*is_enrolled\|is_enrolled.*enrolled_at" app/ >> $REPORT_FILE || true

# Assignment split-brain
echo "### Assignment Split-Brain" >> $REPORT_FILE
grep -r "completed_at.*is_completed\|is_completed.*completed_at" app/ >> $REPORT_FILE || true

# Generate Summary
echo "" >> $REPORT_FILE
echo "## 📊 VIOLATION SUMMARY" >> $REPORT_FILE
echo "" >> $REPORT_FILE

total_violations=0

# Count boolean soup files
boolean_soup_count=$(grep -c "BOOLEAN SOUP" $REPORT_FILE || echo 0)
total_violations=$((total_violations + boolean_soup_count))

# Count boolean clusters
boolean_cluster_count=$(grep -c "BOOLEAN CLUSTER" $REPORT_FILE || echo 0)
total_violations=$((total_violations + boolean_cluster_count))

# Count service violations
service_violations=$(grep -c "SERVICE BOOLEAN SOUP" $REPORT_FILE || echo 0)
total_violations=$((total_violations + service_violations))

echo "- **Boolean Soup Files**: $boolean_soup_count" >> $REPORT_FILE
echo "- **Boolean Cluster Models**: $boolean_cluster_count" >> $REPORT_FILE  
echo "- **Service Violations**: $service_violations" >> $REPORT_FILE
echo "- **Total Violations**: $total_violations" >> $REPORT_FILE
echo "" >> $REPORT_FILE

if [ "$total_violations" -gt 0 ]; then
    echo "🚨 **STATUS**: SPLIT-BRAIN VIOLATIONS DETECTED" >> $REPORT_FILE
    echo "🔥 **ACTION REQUIRED**: Immediate refactoring needed" >> $REPORT_FILE
else
    echo "✅ **STATUS**: NO MAJOR SPLIT-BRAIN VIOLATIONS DETECTED" >> $REPORT_FILE
fi

echo "" >> $REPORT_FILE
echo "---" >> $REPORT_FILE
echo "**Report Generated**: $(date)" >> $REPORT_FILE
echo "**Scan Completed**: Split-Brain Terminator" >> $REPORT_FILE

echo ""
echo "🎯 SCAN COMPLETE!"
echo "📊 Total Violations Found: $total_violations"
echo "📄 Full Report: $REPORT_FILE"
echo ""

if [ "$total_violations" -gt 0 ]; then
    echo "🚨 SPLIT-BRAIN VIOLATIONS DETECTED!"
    echo "🔥 IMMEDIATE ACTION REQUIRED!"
    exit 1
else
    echo "✅ No major split-brain violations found"
    exit 0
fi