/*
  Requirement: Populate the "Course Assignments" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="assignment-list-section"` to the
     <section> element that will contain the assignment articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the assignment list ('#assignment-list-section').
const listSection = document.getElementById('assignment-list-section');

// --- Functions ---

/**
 * TODO: Implement the createAssignmentArticle function.
 * It takes one assignment object {id, title, dueDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Details" link's `href` MUST be set to `details.html?id=${id}`.
 * This is how the detail page will know which assignment to load.
 */
function createAssignmentArticle(assignment) {
  // ... your implementation here ...
  const { id, title, dueDate, description } = assignment;

  const article = document.createElement('article');
  const h2 = document.createElement('h2');
  h2.textContent = title;
  article.appendChild(h2);
  const dueP = document.createElement('p');
  dueP.textContent = `Due: ${dueDate}`;
  article.appendChild(dueP);
  const descP = document.createElement('p');
  descP.textContent = description;
  article.appendChild(descP);
  const detailsLink = document.createElement('a');
  detailsLink.href = `details.html?id=${id}`;
  detailsLink.textContent = 'View Details';
  article.appendChild(detailsLink);
  return article;
}

/**
 * TODO: Implement the loadAssignments function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'assignments.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the assignments array. For each assignment:
 * - Call `createAssignmentArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadAssignments() {
  // ... your implementation here ...
  const response = await fetch('assignments.json');
  const assignments = await response.json();
  listSection.innerHTML = '';
  assignments.forEach(assignment => {
    const article = createAssignmentArticle(assignment);
    listSection.appendChild(article);
  });
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadAssignments();
