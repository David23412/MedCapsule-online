/**
 * BACKUP: HTML Entity Decoding Mechanism for Written Answers
 * 
 * This file contains the HTML entity decoding mechanisms extracted from the
 * solve_quiz.blade.php and question-written.blade.php files.
 * It can be used as a reference for implementing HTML entity decoding in the future.
 * 
 * Backed up on: Date: ${new Date().toISOString().split('T')[0]}
 */

/**
 * Main quiz page decoding function (from solve_quiz.blade.php)
 * Enhanced function for decoding HTML entities
 * 
 * @param {string} text - The HTML-encoded text to decode
 * @return {string} The decoded text
 */
function decodeHtmlEntities(text) {
    if (!text) return '';
    
    try {
        // Create a DOM element to leverage browser's built-in decoder
        const textArea = document.createElement('textarea');
        textArea.innerHTML = text;
        const decodedText = textArea.value;
        
        // Additional replacements for common entities that might not be handled correctly
        return decodedText
            .replace(/&quot;/g, '"')
            .replace(/&#039;/g, "'")
            .replace(/&amp;/g, "&")
            .replace(/&lt;/g, "<")
            .replace(/&gt;/g, ">")
            .replace(/&#92;/g, "\\")
            .replace(/&#x27;/g, "'")
            .replace(/&#x2F;/g, "/")
            .replace(/&#x60;/g, "`")
            .replace(/&#x3D;/g, "=")
            .replace(/&ldquo;/g, """)
            .replace(/&rdquo;/g, """)
            .replace(/&lsquo;/g, "'")
            .replace(/&rsquo;/g, "'")
            .replace(/&lpar;/g, "(")
            .replace(/&rpar;/g, ")")
            .replace(/&lsqb;/g, "[")
            .replace(/&rsqb;/g, "]")
            .replace(/&lcub;/g, "{")
            .replace(/&rcub;/g, "}")
            .replace(/&plus;/g, "+")
            .replace(/&ast;/g, "*");
    } catch (e) {
        console.error('Error decoding HTML entities:', e);
        return text; // Return the original text if decoding fails
    }
}

/**
 * Written question component decoding function (from question-written.blade.php)
 * Improved helper function for decoding HTML entities
 * 
 * @param {string} text - The HTML-encoded text to decode
 * @return {string} The decoded text
 */
function decodeHtmlEntitiesAdvanced(text) {
    if (!text) return '';
    
    try {
        // Create a DOM text node and use the browser's built-in decoder
        const textArea = document.createElement('textarea');
        textArea.innerHTML = text;
        const decodedText = textArea.value;
        
        // Additional handling for special quotation marks and brackets
        return decodedText
            .replace(/&quot;/g, '"')
            .replace(/&#039;/g, "'")
            .replace(/&lt;/g, "<")
            .replace(/&gt;/g, ">")
            .replace(/&amp;/g, "&")
            .replace(/&#92;/g, "\\")
            .replace(/&#x27;/g, "'")
            .replace(/&#x2F;/g, "/")
            .replace(/&#x60;/g, "`")
            .replace(/&#x3D;/g, "=")
            .replace(/&ldquo;/g, """)
            .replace(/&rdquo;/g, """)
            .replace(/&lsquo;/g, "'")
            .replace(/&rsquo;/g, "'")
            .replace(/&lpar;/g, "(")
            .replace(/&rpar;/g, ")")
            .replace(/&lsqb;/g, "[")
            .replace(/&rsqb;/g, "]")
            .replace(/&lcub;/g, "{")
            .replace(/&rcub;/g, "}")
            .replace(/&plus;/g, "+")
            .replace(/&ast;/g, "*");
    } catch (e) {
        console.error('Error decoding HTML entities:', e);
        return text; // Return original text if there's an error
    }
}

/**
 * Usage Example:
 * 
 * // In the main quiz page:
 * const encodedText = "&lt;p&gt;This is a &quot;test&quot;&lt;/p&gt;";
 * const decodedText = decodeHtmlEntities(encodedText);
 * // Result: <p>This is a "test"</p>
 * 
 * // In the written question component:
 * const encodedCorrectAnswer = "The heart is a &quot;pump&quot; that (circulates) blood.";
 * const decodedCorrectAnswer = decodeHtmlEntitiesAdvanced(encodedCorrectAnswer);
 * // Result: The heart is a "pump" that (circulates) blood.
 */ 