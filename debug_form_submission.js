// Debug script to test form submission
console.log('🚨 DEBUG SCRIPT LOADED');

$(document).ready(function() {
    console.log('🚨 Document ready');
    console.log('🚨 Form exists:', $('#enrollmentForm').length);
    console.log('🚨 Button exists:', $('#grantAccessBtn').length);
    
    // Test if form submission is being captured
    $('#enrollmentForm').on('submit', function(e) {
        console.log('🚨 FORM SUBMIT CAPTURED BY DEBUG SCRIPT!');
        e.preventDefault();
        alert('Form submission captured by debug script!');
        return false;
    });
    
    // Test button click
    $('#grantAccessBtn').on('click', function(e) {
        console.log('🚨 BUTTON CLICK CAPTURED BY DEBUG SCRIPT!');
        alert('Button click captured!');
    });
    
    // Test if there are multiple handlers
    const events = $._data($('#enrollmentForm')[0], 'events');
    console.log('🚨 Form events:', events);
    
    const buttonEvents = $._data($('#grantAccessBtn')[0], 'events');
    console.log('🚨 Button events:', buttonEvents);
});