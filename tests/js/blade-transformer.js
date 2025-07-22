/**
 * Blade Template Transformer for Jest
 * Transforms Blade templates to JavaScript for testing
 */

const fs = require('fs');
const path = require('path');

module.exports = {
    process(src, filename) {
        // Extract the blade template content
        const templateContent = fs.readFileSync(filename, 'utf8');
        
        // Simple transformation: convert blade template to a mock JavaScript module
        // This extracts key elements for testing without full blade compilation
        
        // Extract data attributes, classes, and IDs for testing
        const dataAttributes = extractDataAttributes(templateContent);
        const classes = extractClasses(templateContent);
        const ids = extractIds(templateContent);
        const ariaAttributes = extractAriaAttributes(templateContent);
        
        // Create a mock module that exposes the template structure
        const mockModule = `
            module.exports = {
                template: ${JSON.stringify(templateContent)},
                dataAttributes: ${JSON.stringify(dataAttributes)},
                classes: ${JSON.stringify(classes)},
                ids: ${JSON.stringify(ids)},
                ariaAttributes: ${JSON.stringify(ariaAttributes)},
                
                // Mock DOM methods for testing
                querySelector: function(selector) {
                    return {
                        getAttribute: function(attr) {
                            if (attr.startsWith('data-')) {
                                return dataAttributes[attr] || null;
                            }
                            if (attr.startsWith('aria-')) {
                                return ariaAttributes[attr] || null;
                            }
                            return null;
                        },
                        classList: {
                            contains: function(className) {
                                return classes.includes(className);
                            }
                        },
                        id: ids[0] || null
                    };
                },
                
                querySelectorAll: function(selector) {
                    // Mock multiple elements
                    return [{
                        getAttribute: function(attr) {
                            if (attr.startsWith('data-')) {
                                return dataAttributes[attr] || null;
                            }
                            if (attr.startsWith('aria-')) {
                                return ariaAttributes[attr] || null;
                            }
                            return null;
                        }
                    }];
                }
            };
        `;
        
        return mockModule;
    }
};

// Helper functions to extract template information
function extractDataAttributes(content) {
    const dataAttrs = {};
    const dataRegex = /data-([a-zA-Z-]+)=["']([^"']+)["']/g;
    let match;
    
    while ((match = dataRegex.exec(content)) !== null) {
        dataAttrs[`data-${match[1]}`] = match[2];
    }
    
    return dataAttrs;
}

function extractClasses(content) {
    const classes = [];
    const classRegex = /class=["']([^"']+)["']/g;
    let match;
    
    while ((match = classRegex.exec(content)) !== null) {
        const classNames = match[1].split(/\s+/);
        classes.push(...classNames);
    }
    
    return [...new Set(classes)]; // Remove duplicates
}

function extractIds(content) {
    const ids = [];
    const idRegex = /id=["']([^"']+)["']/g;
    let match;
    
    while ((match = idRegex.exec(content)) !== null) {
        ids.push(match[1]);
    }
    
    return ids;
}

function extractAriaAttributes(content) {
    const ariaAttrs = {};
    const ariaRegex = /aria-([a-zA-Z-]+)=["']([^"']+)["']/g;
    let match;
    
    while ((match = ariaRegex.exec(content)) !== null) {
        ariaAttrs[`aria-${match[1]}`] = match[2];
    }
    
    return ariaAttrs;
}