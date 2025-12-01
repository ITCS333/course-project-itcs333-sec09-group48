/*
  Requirement: Make the "Manage Assignments" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="assignments-tbody"` to the <tbody> element
     so you can select it.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the assignments loaded from the JSON file.
let assignments = [];

// --- Element Selections ---
// TODO: Select the assignment form ('#assignment-form').
const assignmentForm = document.getElementById('assignment-form');

// TODO: Select the assignments table body ('#assignments-tbody').
const assignmentsTableBody = document.getElementById('assignments-tbody');

// --- Functions ---

/**
 * TODO: Implement the createAssignmentRow function.
 * It takes one assignment object {id, title, dueDate}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `dueDate`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createAssignmentRow(assignment) {
    const row = document.createElement('tr');
    
    const titleCell = document.createElement('td');
    titleCell.textContent = assignment.title;
    
    const dueDateCell = document.createElement('td');
    // Format date if needed, or just display as-is
    dueDateCell.textContent = assignment.dueDate;
    
    const actionsCell = document.createElement('td');
    
    const editButton = document.createElement('button');
    editButton.textContent = 'Edit';
    editButton.className = 'edit-btn';
    editButton.setAttribute('data-id', assignment.id);
    
    const deleteButton = document.createElement('button');
    deleteButton.textContent = 'Delete';
    deleteButton.className = 'delete-btn';
    deleteButton.setAttribute('data-id', assignment.id);
    
    actionsCell.appendChild(editButton);
    actionsCell.appendChild(deleteButton);
    
    row.appendChild(titleCell);
    row.appendChild(dueDateCell);
    row.appendChild(actionsCell);
    
    return row;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `assignmentsTableBody`.
 * 2. Loop through the global `assignments` array.
 * 3. For each assignment, call `createAssignmentRow()`, and
 * append the resulting <tr> to `assignmentsTableBody`.
 */
function renderTable() {
    assignmentsTableBody.innerHTML = '';
    
    assignments.forEach(assignment => {
        const row = createAssignmentRow(assignment);
        assignmentsTableBody.appendChild(row);
    });
}

/**
 * TODO: Implement the handleAddAssignment function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, description, due date, and files inputs.
 * 3. Create a new assignment object with a unique ID (e.g., `id: \`asg_${Date.now()}\``).
 * 4. Add this new assignment object to the global `assignments` array (in-memory only).
 * 5. Call `renderTable()` to refresh the list.
 * 6. Reset the form.
 */
function handleAddAssignment(event) {
    event.preventDefault();
    
    const title = document.getElementById('assignment-title').value;
    const description = document.getElementById('assignment-description').value;
    const dueDate = document.getElementById('assignment-due-date').value;
    const filesInput = document.getElementById('assignment-files').value;
    
    if (!title || !dueDate) {
        alert('Please fill out all required fields (Title and Due Date)');
        return;
    }
    
    // Convert files text (one per line) to array
    const files = filesInput 
        ? filesInput.split('\n').map(line => line.trim()).filter(line => line !== '')
        : [];
    
    const newAssignment = {
        id: `asg_${Date.now()}`,
        title,
        description,
        dueDate,
        files
    };
    
    assignments.push(newAssignment);
    renderTable();
    assignmentForm.reset();
}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `assignmentsTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `assignments` array by filtering out the assignment
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
function handleTableClick(event) {
    const target = event.target;
    
    if (target.classList.contains('delete-btn')) {
        const id = target.getAttribute('data-id');
        assignments = assignments.filter(a => a.id !== id);
        renderTable();
    }
    
    // You can add edit functionality later
    if (target.classList.contains('edit-btn')) {
        const id = target.getAttribute('data-id');
        console.log('Edit assignment with id:', id);
        // Add edit functionality here
    }
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'assignments.json'.
 * 2. Parse the JSON response and store the result in the global `assignments` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `assignmentForm` (calls `handleAddAssignment`).
 * 5. Add the 'click' event listener to `assignmentsTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
    try {
        console.log('Loading assignments from: assignments.json');
        
        // Try different paths based on your file structure
        // If your structure is: assignments/api/assignments.json
        const response = await fetch('api/assignments.json');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        assignments = await response.json();
        console.log('Successfully loaded assignments:', assignments);
        
        // Verify assignments is an array
        if (!Array.isArray(assignments)) {
            console.warn('assignments.json does not contain an array');
            assignments = [];
        }
        
        renderTable();
        
        // Add event listeners
        assignmentForm.addEventListener('submit', handleAddAssignment);
        assignmentsTableBody.addEventListener('click', handleTableClick);
        
        console.log('Application initialized successfully');
        
    } catch (error) {
        console.error('Error loading assignments:', error);
                            
        // Use fallback data matching your JSON structure
        assignments = [
            {
                "id": "asg_1",
                "title": "Assignment 1: HTML Basics",
                "description": "Create a semantic HTML structure for a personal portfolio. Focus on using tags like <header>, <nav>, <main>, <article>, and <footer>.",
                "dueDate": "2025-11-10",
                "files": ["portfolio-requirements.pdf", "examples.zip"]
            },
            {
                "id": "asg_2",
                "title": "Assignment 2: CSS Styling",
                "description": "Style your HTML portfolio using modern CSS. You must use Flexbox or Grid for layout and include at least one CSS animation.",
                "dueDate": "2025-11-17",
                "files": ["style-guide.pdf"]
            },
            {
                "id": "asg_3",
                "title": "Assignment 3: JavaScript Events",
                "description": "Make your portfolio interactive. Add event listeners to create a modal window for your projects and a theme switcher (light/dark mode).",
                "dueDate": "2025-11-24",
                "files": ["js-requirements.pdf", "event-listeners-guide.txt"]
            }
        ];
        
        renderTable();
        
        // Still add event listeners
        assignmentForm.addEventListener('submit', handleAddAssignment);
        assignmentsTableBody.addEventListener('click', handleTableClick);
        
        alert('Could not load assignments from file. Using default assignments.');
    }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();