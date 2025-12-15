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
async function handleAddAssignment(event) {
    event.preventDefault();
    
    const title = document.getElementById('assignment-title').value;
    const description = document.getElementById('assignment-description').value;
    const dueDate = document.getElementById('assignment-due-date').value;
    const filesInput = document.getElementById('assignment-files').value;
    
    if (!title || !dueDate) {
        alert('Please fill out all required fields (Title and Due Date)');
        return;
    }
    
    const files = filesInput 
        ? filesInput.split('\n').map(line => line.trim()).filter(line => line !== '')
        : [];
    
    const newAssignment = {
        title,
        description,
        due_date: dueDate,
        files
    };
    
    try {
        const response = await fetch('api/?resource=assignments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: newAssignment.title,
                description: newAssignment.description,
                due_date: newAssignment.due_date,
                files: newAssignment.files
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            assignments.push({
                id: result.data.id,
                title: result.data.title,
                description: result.data.description,
                dueDate: result.data.due_date,
                files: result.data.files || []
            });
            renderTable();
            assignmentForm.reset();
        } else {
            alert('Failed to create assignment: ' + result.message);
        }
    } catch (error) {
        console.error('Error creating assignment:', error);
        alert('Failed to create assignment. Please try again.');
    }
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
async function handleTableClick(event) {
    const target = event.target;
    
    if (target.classList.contains('delete-btn')) {
        const id = target.getAttribute('data-id');
        
        if (!confirm('Are you sure you want to delete this assignment?')) {
            return;
        }
        
        try {
            const response = await fetch(`api/?resource=assignments&id=${id}`, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            
            if (result.success) {
                assignments = assignments.filter(a => a.id !== id && a.id !== parseInt(id));
                renderTable();
            } else {
                alert('Failed to delete assignment: ' + result.message);
            }
        } catch (error) {
            console.error('Error deleting assignment:', error);
            alert('Failed to delete assignment. Please try again.');
        }
    }
    
    if (target.classList.contains('edit-btn')) {
        const id = target.getAttribute('data-id');
        editAssignment(id);
    }
}

// Edit functionality (optional - keep if you need it)
function editAssignment(id) {
    const assignmentToEdit = assignments.find(a => a.id === id);
    
    if (!assignmentToEdit) {
        console.error('Assignment not found for editing');
        return;
    }
    
    document.getElementById('assignment-title').value = assignmentToEdit.title;
    document.getElementById('assignment-description').value = assignmentToEdit.description;
    document.getElementById('assignment-due-date').value = assignmentToEdit.dueDate;
    
    const filesText = Array.isArray(assignmentToEdit.files) 
        ? assignmentToEdit.files.join('\n')
        : assignmentToEdit.files || '';
    document.getElementById('assignment-files').value = filesText;
    
    const submitButton = document.getElementById('add-assignment');
    submitButton.textContent = 'Update Assignment';
    submitButton.classList.add('updating');
    
    const form = document.getElementById('assignment-form');
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    
    newForm.addEventListener('submit', function(event) {
        event.preventDefault();
        updateAssignment(id);
    });
    
    if (!document.getElementById('cancel-edit')) {
        const cancelButton = document.createElement('button');
        cancelButton.id = 'cancel-edit';
        cancelButton.type = 'button';
        cancelButton.textContent = 'Cancel Edit';
        cancelButton.addEventListener('click', resetForm);
        
        newForm.querySelector('fieldset').appendChild(cancelButton);
    }
}

async function updateAssignment(id) {
    const title = document.getElementById('assignment-title').value;
    const description = document.getElementById('assignment-description').value;
    const dueDate = document.getElementById('assignment-due-date').value;
    const filesInput = document.getElementById('assignment-files').value;
    
    if (!title || !dueDate) {
        alert('Please fill out all required fields (Title and Due Date)');
        return;
    }
    
    const files = filesInput 
        ? filesInput.split('\n').map(line => line.trim()).filter(line => line !== '')
        : [];
    
    try {
        const response = await fetch('api/?resource=assignments', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                title,
                description,
                due_date: dueDate,
                files
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const index = assignments.findIndex(a => a.id === id || a.id === parseInt(id));
            if (index !== -1) {
                assignments[index] = {
                    ...assignments[index],
                    title,
                    description,
                    dueDate,
                    files
                };
            }
            renderTable();
            resetForm();
            alert('Assignment updated successfully!');
        } else {
            alert('Failed to update assignment: ' + result.message);
        }
    } catch (error) {
        console.error('Error updating assignment:', error);
        alert('Failed to update assignment. Please try again.');
    }
}

function resetForm() {
    const form = document.getElementById('assignment-form');
    form.reset();
    
    const submitButton = document.getElementById('add-assignment');
    submitButton.textContent = 'Add Assignment';
    submitButton.classList.remove('updating');
    
    const cancelButton = document.getElementById('cancel-edit');
    if (cancelButton) {
        cancelButton.remove();
    }
    
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    newForm.addEventListener('submit', handleAddAssignment);
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
        const response = await fetch('api/?resource=assignments');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success && Array.isArray(result.data)) {
            assignments = result.data.map(a => ({
                id: a.id,
                title: a.title,
                description: a.description,
                dueDate: a.due_date,
                files: a.files || []
            }));
        } else {
            assignments = [];
        }
        
        renderTable();
        
        assignmentForm.addEventListener('submit', handleAddAssignment);
        assignmentsTableBody.addEventListener('click', handleTableClick);
        
    } catch (error) {
        console.error('Error loading assignments:', error);
        
        assignments = [];
        renderTable();
        
        assignmentForm.addEventListener('submit', handleAddAssignment);
        assignmentsTableBody.addEventListener('click', handleTableClick);
        
        console.log('Could not load assignments from API. Starting with empty list.');
    }
}

// --- Initial Page Load ---
loadAndInitialize();