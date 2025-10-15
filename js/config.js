/**
 * Configuration and Data for Church Membership Registration Form
 * Contains all constants and data structures used across the application
 */

/**
 * Family groups data - organized by cell group
 * Centralized for easy updates
 */
const familyGroups = {
    'GACHORUE': ['BETHSAIDA', 'JUDEA', 'SAMARIA', 'CANAAN', 'JERICHO'],
    'MOMBASA': ['BETHANY', 'BETHLEHEM', 'EMMAUS', 'JOPPA', 'BETHSAIDA'],
    'POSTAA': ['ST. PAUL', 'ST. PETER', 'ELISHA', 'DANIEL'],
    'POSTA B': ['CALEB', 'MOSES', 'HARUN'],
    'KAMBARA': ['ST. PAUL', 'ST. PETER', 'ST. JOHN', 'DEBORAH'],
    'GITHIRIA': ['ISAIAH', 'EZEKIEL', 'JEREMIAH']
};

// Global counters for dynamic fields
let leadershipRoleCounter = 1;
let employmentRoleCounter = 1;

// Maximum number of selections for ministry/department checkboxes
const MAX_SELECTIONS = 5;