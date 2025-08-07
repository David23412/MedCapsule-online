// Debug script to test form submission
// console.log('🚨 DEBUG SCRIPT LOADED'); // Removed for production

$(document).ready(function() {
// console.log('🚨 Document ready'); // Removed for production
// console.log('🚨 Form exists:', $('#enrollmentForm').length); // Removed for production
// console.log('🚨 Button exists:', $('#grantAccessBtn').length); // Removed for production
    
    // Test if form submission is being captured
    $('#enrollmentForm').on('submit', function(e) {
// console.log('🚨 FORM SUBMIT CAPTURED BY DEBUG SCRIPT!'); // Removed for production
        e.preventDefault();
        alert('Form submission captured by debug script!');
        return false;
    });
    
    // Test button click
    $('#grantAccessBtn').on('click', function(e) {
// console.log('🚨 BUTTON CLICK CAPTURED BY DEBUG SCRIPT!'); // Removed for production
        alert('Button click captured!');
    });
    
    // Test if there are multiple handlers
    const events = $._data($('#enrollmentForm')[0], 'events');
// console.log('🚨 Form events:', events); // Removed for production
    
    const buttonEvents = $._data($('#grantAccessBtn')[0], 'events');
// console.log('🚨 Button events:', buttonEvents); // Removed for production
});