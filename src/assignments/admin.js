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
  //alerts
  alert(`createAssignmentRow called for: ${assignment.title}`);


  const { id, title, dueDate } = assignment;

  const tr = document.createElement('tr');

  // Title cell
  const titleTd = document.createElement('td');
  titleTd.textContent = title;
  tr.appendChild(titleTd);

  // Due date cell
  const dueTd = document.createElement('td');
  dueTd.textContent = dueDate;
  tr.appendChild(dueTd);

  // Actions cell with buttons
  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.type = 'button';
  editBtn.className = 'edit-assignment'; // Match HTML class
  editBtn.dataset.id = id;
  editBtn.textContent = 'Edit';

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.className = 'delete-assignment'; // Match HTML class
  deleteBtn.dataset.id = id;
  deleteBtn.textContent = 'Delete';

  actionsTd.append(editBtn, deleteBtn);
  tr.appendChild(actionsTd);

  return tr;
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
  //alerts
   alert(`renderTable called. Number of assignments: ${assignments.length}`);

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
  //alerts
  alert('handleAddAssignment called');
  
  // Get values directly from input elements (matching HTML IDs)
  const titleInput = document.getElementById('assignment-title');
  const descriptionInput = document.getElementById('assignment-description');
  const dueDateInput = document.getElementById('assignment-due-date');
  const filesInput = document.getElementById('assignment-files');

  //alerts
  alert(`Input values - Title: ${title}, Due: ${dueDate}, Description: ${description}`);

  const title = titleInput.value.trim();
  const description = descriptionInput.value.trim();
  const dueDate = dueDateInput.value;
  const filesText = filesInput.value.trim();

  // Basic validation
  if (!title) {
    alert('Please enter a title');
    return;
  }
  if (!dueDate) {
    alert('Please select a due date');
    return;
  }

  // Process files (split by newline)
  const files = filesText ? filesText.split('\n').map(line => line.trim()).filter(line => line) : [];

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
  const btn = event.target;

  //alerts
  alert(`handleTableClick called on element: ${btn.tagName}, class: ${btn.className}`);
  
  // DELETE
  if (btn.classList.contains('delete-assignment')) {
    //alerts
    alert(`Deleting assignment with id: ${btn.dataset.id}`);


    let id = btn.dataset.id;

    // If button has no data-id (HTML dummy row)
    if (!id) {
      const row = btn.closest('tr');
      if (row) row.remove();
      return;
    }

    assignments = assignments.filter(a => a.id !== id);
    renderTable();
  }
  
  // EDIT item (not implemented, just alert)
  if (btn.classList.contains('edit-assignment')) {
    //alerts
    alert(`Edit clicked for assignment id: ${btn.dataset.id}`);

    let id = btn.dataset.id;

    if (!id) {
      alert("This static HTML row cannot be edited.");
      return;
    }

    alert(`Edit functionality for assignment ${id} would go here`);
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
  //alerts
  alert('loadAndInitialize called');

  try {
    // For demo purposes, we'll use some sample data since assignments.json might not exist
    assignments = [
      { id: 'asg_1', title: 'HTML Basics', dueDate: '2024-07-15', description: 'Learn basic HTML tags', files: [] },
      { id: 'asg_2', title: 'CSS Styling', dueDate: '2024-07-22', description: 'Style web pages with CSS', files: [] }
    ];

    //alerts
    alert(`Sample assignments loaded: ${assignments.map(a => a.title).join(', ')}`);
    
    // If you have a real assignments.json file, uncomment this:
    /*
    const res = await fetch('assignments.json');
    if (!res.ok) throw new Error(`Fetch failed: ${res.status} ${res.statusText}`);
    const data = await res.json();
    assignments = Array.isArray(data) ? data : [];
    */
    
  } catch (err) {
    console.error('Error loading assignments:', err);
    // Fallback to empty array
    assignments = [];
  }

  renderTable();

  if (assignmentForm) {
    assignmentForm.addEventListener('submit', handleAddAssignment);
  }
  if (assignmentsTableBody) {
    assignmentsTableBody.addEventListener('click', handleTableClick);
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();