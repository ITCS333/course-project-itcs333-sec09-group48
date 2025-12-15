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
  const article = document.createElement('article');
  
  const h2 = document.createElement('h2');
  h2.textContent = assignment.title;
  article.appendChild(h2);
  
  const dueDatePara = document.createElement('p');
  dueDatePara.textContent = `Due: ${assignment.dueDate}`;
  article.appendChild(dueDatePara);
  
  const descriptionPara = document.createElement('p');
  descriptionPara.textContent = assignment.description;
  article.appendChild(descriptionPara);
  
  const detailsLink = document.createElement('a');
  detailsLink.href = `details.html?id=${assignment.id}`;
  detailsLink.textContent = 'View Details & Discussion';
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
    console.log('Loading assignments from API');
    
    // Fetch from PHP API
    const response = await fetch('api/?resource=assignments');
    
    if (!response.ok) {
      throw new Error(`Failed to load assignments: ${response.status}`);
    }
    
    const result = await response.json();
    console.log('Loaded assignments:', result);
    
    // Handle API response format (data is in result.data)
    const assignments = result.success && result.data ? result.data : [];
    
    // Clear the section (removes the hardcoded HTML assignments)
    if (listSection) {
      listSection.innerHTML = '';
      
      if (!Array.isArray(assignments) || assignments.length === 0) {
        listSection.innerHTML = '<p>No assignments available at this time.</p>';
        return;
      }
      
      // Create and append each assignment
      assignments.forEach(assignment => {
        // Map API fields to expected format
        const assignmentData = {
          id: assignment.id,
          title: assignment.title,
          dueDate: assignment.due_date,
          description: assignment.description
        };
        const article = createAssignmentArticle(assignmentData);
        listSection.appendChild(article);
      });
    }
    
  } catch (error) {
    console.error('Error loading assignments:', error);
    
    // Show error to user
    if (listSection) {
      listSection.innerHTML = `
        <div class="error">
          <p>Unable to load assignments. Please try again later.</p>
          <p><small>${error.message}</small></p>
        </div>
      `;
      
      // Fallback to sample data after showing error
      setTimeout(loadSampleData, 2000);
    }
  }
}

/**
 * Fallback function with sample data matching your actual assignments
 */
function loadSampleData() {
  const sampleAssignments = [
    {
      id: "asg_1",
      title: "Assignment 1: HTML Basics",
      dueDate: "2025-11-10",
      description: "Create a semantic HTML structure for a personal portfolio. Focus on using tags like <header>, <nav>, <main>, <article>, and <footer>."
    },
    {
      id: "asg_2",
      title: "Assignment 2: CSS Styling", 
      dueDate: "2025-11-17",
      description: "Style your HTML portfolio using modern CSS. You must use Flexbox or Grid for layout and include at least one CSS animation."
    },
    {
      id: "asg_3",
      title: "Assignment 3: JavaScript Events",
      dueDate: "2025-11-24",
      description: "Make your portfolio interactive. Add event listeners to create a modal window for your projects and a theme switcher (light/dark mode)."
    }
  ];
  
  if (listSection) {
    listSection.innerHTML = '';
    sampleAssignments.forEach(assignment => {
      const article = createAssignmentArticle(assignment);
      listSection.appendChild(article);
    });
    
    // Add note about using sample data
    const note = document.createElement('p');
    note.style.fontStyle = 'italic';
    note.style.color = '#666';
    note.style.marginTop = '20px';
    note.textContent = 'Note: Displaying sample assignment data.';
    listSection.appendChild(note);
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadAssignments();