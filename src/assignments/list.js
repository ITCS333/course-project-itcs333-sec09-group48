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
  const { id, title, dueDate, description } = assignment;

  // Create the main article element
  const article = document.createElement('article');
  article.className = 'assignment-item'; // Add a class for styling

  // Create and append the title (h2)
  const h2 = document.createElement('h2');
  h2.textContent = title;
  article.appendChild(h2);

  // Create and append the due date (p)
  const dueP = document.createElement('p');
  dueP.className = 'due-date';
  dueP.textContent = `Due: ${dueDate}`;
  article.appendChild(dueP);

  // Create and append the description (p)
  const descP = document.createElement('p');
  descP.className = 'description';
  
  // Truncate long descriptions for the list view
  const shortDescription = description.length > 150 
    ? description.substring(0, 150) + '...' 
    : description;
  descP.textContent = shortDescription;
  article.appendChild(descP);

  // Create and append the "View Details" link
  const detailsLink = document.createElement('a');
  detailsLink.href = `details.html?id=${id}`;
  detailsLink.textContent = 'View Details';
  detailsLink.className = 'view-details-btn';
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
  try {
    // Show loading state
    if (listSection) {
      listSection.innerHTML = '<p class="loading">Loading assignments...</p>';
    }

    const response = await fetch('assignments.json');
    
    if (!response.ok) {
      throw new Error(`Failed to load assignments: ${response.status} ${response.statusText}`);
    }
    
    const assignments = await response.json();
    
    // Validate that we got an array
    if (!Array.isArray(assignments)) {
      throw new Error('Invalid data format: assignments data is not an array');
    }
    
    // Clear section
    if (listSection) {
      listSection.innerHTML = '';
      
      // Handle empty assignments array
      if (assignments.length === 0) {
        listSection.innerHTML = '<p class="no-assignments">No assignments available.</p>';
        return;
      }
      
      // Create and append articles for each assignment
      assignments.forEach(assignment => {
        const article = createAssignmentArticle(assignment);
        listSection.appendChild(article);
      });
    }
    
  } catch (error) {
    console.error('Error loading assignments:', error);
    
    // Show error message to user
    if (listSection) {
      listSection.innerHTML = `
        <div class="error-message">
          <p>Unable to load assignments at this time.</p>
          <p><small>Error: ${error.message}</small></p>
          <button onclick="loadAssignments()" class="retry-btn">Try Again</button>
        </div>
      `;
      
      // Fallback: Load sample data if fetch fails
      setTimeout(() => {
        loadSampleData();
      }, 2000);
    }
  }
}

/**
 * Fallback function to load sample data when JSON file is not available
 */
function loadSampleData() {
  const sampleAssignments = [
    {
      id: 'asg_1',
      title: 'HTML Basics',
      dueDate: '2024-07-15',
      description: 'Learn the fundamentals of HTML including tags, attributes, and document structure. Create a simple webpage using semantic HTML elements.'
    },
    {
      id: 'asg_2', 
      title: 'CSS Styling',
      dueDate: '2024-07-22',
      description: 'Explore CSS properties and selectors to style web pages. Practice using flexbox and grid for layout design.'
    },
    {
      id: 'asg_3',
      title: 'JavaScript Fundamentals',
      dueDate: '2024-07-29',
      description: 'Introduction to JavaScript programming concepts including variables, functions, and DOM manipulation.'
    }
  ];

  if (listSection) {
    listSection.innerHTML = '';
    sampleAssignments.forEach(assignment => {
      const article = createAssignmentArticle(assignment);
      listSection.appendChild(article);
    });
    
    // Add a note that sample data is being used
    const note = document.createElement('p');
    note.className = 'sample-data-note';
    note.textContent = 'Note: Sample assignments are being displayed.';
    note.style.fontStyle = 'italic';
    note.style.color = '#666';
    note.style.marginTop = '20px';
    listSection.appendChild(note);
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadAssignments();