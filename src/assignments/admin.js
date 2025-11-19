/*
  Requirement: Make the "Manage Assignments" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="assignments-tbody"` to the <tbody> element
     so you can select it.
  
  3. Implement the TODOs below.
*/
<script src="admin.js" defer></script>

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
  editBtn.className = 'edit-btn';
  editBtn.dataset.id = id;
  editBtn.textContent = 'Edit';

  const deleteBtn = document.createElement('button');
  deleteBtn.type = 'button';
  deleteBtn.className = 'delete-btn';
  deleteBtn.dataset.id = id;
  deleteBtn.textContent = 'Delete';

  actionsTd.append(editBtn, deleteBtn); // Using append for multiple elements (instead of appendChild)
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
  // ... your implementation here ...
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
  
  const formData = new FormData(assignmentForm);
  const title = (formData.get('title') || '').toString().trim();
  const description = (formData.get('description') || '').toString().trim();
  const dueDate = (formData.get('dueDate') || '').toString().trim();

  // Basic validation
  if (!title) {
    alert('Please enter a title');
    return;
  }

  // File handling - adjust based on your actual form structure
  const files = [];
  const fileInputs = formData.getAll('files');
  fileInputs.forEach(file => {
    if (file instanceof File && file.name) {
      files.push(file.name);
    }
  });

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
  // ... your implementation here ...
  const btn = event.target.closest('button');
  if (!btn) return;

  if (btn.classList.contains('delete-btn')) {
    const id = btn.dataset.id;
    if (!id) return;
    assignments = assignments.filter(a => a.id !== id);
    renderTable();
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
  // ... your implementation here ...
  try {
    const res = await fetch('assignments.json');
    if (!res.ok) throw new Error(`Fetch failed: ${res.status} ${res.statusText}`);
    const data = await res.json();
    assignments = Array.isArray(data) ? data : [];
  } catch (err) {
    console.error('Error loading assignments.json:', err);
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
