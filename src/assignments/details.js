/*
  Requirement: Populate the assignment detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="assignment-title"`
     - To the "Due" <p>: `id="assignment-due-date"`
     - To the "Description" <p>: `id="assignment-description"`
     - To the "Attached Files" <ul>: `id="assignment-files-list"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Add a Comment" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment-text"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* assignment.
let currentAssignmentId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.

const assignmentTitle = document.getElementById('assignment-title');
const assignmentDueDate = document.getElementById('assignment-due-date');
const assignmentDescription = document.getElementById('assignment-description'); // Fixed typo: removed space
const assignmentFilesList = document.getElementById('assignment-files-list');
const commentList = document.getElementById('comment-list');
const commentForm = document.getElementById('comment-form');
const newCommentText = document.getElementById('new-comment-text');

// --- Functions ---

/**
 * TODO: Implement the getAssignmentIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getAssignmentIdFromURL() {
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  return urlParams.get('id');
}

/**
 * TODO: Implement the renderAssignmentDetails function.
 * It takes one assignment object.
 * It should:
 * 1. Set the `textContent` of `assignmentTitle` to the assignment's title.
 * 2. Set the `textContent` of `assignmentDueDate` to "Due: " + assignment's dueDate.
 * 3. Set the `textContent` of `assignmentDescription`.
 * 4. Clear `assignmentFilesList` and then create and append
 * `<li><a href="#">...</a></li>` for each file in the assignment's 'files' array.
 */
function renderAssignmentDetails(assignment) {
  // Set basic assignment information
  assignmentTitle.textContent = assignment.title;
  assignmentDueDate.textContent = `Due: ${assignment.dueDate}`;
  assignmentDescription.textContent = assignment.description;
  
  // Clear and rebuild files list
  assignmentFilesList.innerHTML = '';
  
  // Handle case where there are no files
  if (!assignment.files || assignment.files.length === 0) {
    const li = document.createElement('li');
    li.textContent = 'No files attached';
    li.className = 'no-files';
    assignmentFilesList.appendChild(li);
    return;
  }
  
  // Create file list items - handle both string format and object format
  assignment.files.forEach(file => {
    const li = document.createElement('li');
    
    // Handle different file formats:
    // - If file is a string (from your HTML form), treat it as URL
    // - If file is an object with url/name properties, use those
    if (typeof file === 'string') {
      const a = document.createElement('a');
      a.href = file.startsWith('http') ? file : `#${file}`;
      a.textContent = file;
      a.target = '_blank';
      a.rel = 'noopener noreferrer';
      li.appendChild(a);
    } else if (file.url) {
      const a = document.createElement('a');
      a.href = file.url;
      a.textContent = file.name || file.url;
      a.target = '_blank';
      a.rel = 'noopener noreferrer';
      li.appendChild(a);
    } else {
      // Fallback: just display as text
      li.textContent = file;
    }
    
    assignmentFilesList.appendChild(li);
  });
}

/**
 * TODO: Implement the createCommentArticle function.
 * It takes one comment object {author, text}.
 * It should return an <article> element matching the structure in `details.html`.
 */
function createCommentArticle(comment) {
  const { author, text } = comment;

  // Create elements
  const article = document.createElement('article');
  const authorP = document.createElement('p');
  const textP = document.createElement('p');
  
  // Set attributes and content
  article.className = 'comment';
  authorP.className = 'comment-author';
  authorP.textContent = author;
  textP.className = 'comment-text';
  textP.textContent = text;
  
  // Build structure
  article.append(authorP, textP);
  
  return article;
}

/**
 * TODO: Implement the renderComments function.
 * It should:
 * 1. Clear the `commentList`.
 * 2. Loop through the global `currentComments` array.
 * 3. For each comment, call `createCommentArticle()`, and
 * append the resulting <article> to `commentList`.
 */
function renderComments() {
  commentList.innerHTML = '';
  
  // Handle case where there are no comments
  if (!currentComments || currentComments.length === 0) {
    const noCommentsMsg = document.createElement('p');
    noCommentsMsg.textContent = 'No comments yet. Be the first to comment!';
    noCommentsMsg.className = 'no-comments';
    commentList.appendChild(noCommentsMsg);
    return;
  }
  
  currentComments.forEach(comment => {
    const commentArticle = createCommentArticle(comment);
    commentList.appendChild(commentArticle);
  });
}

/**
 * TODO: Implement the handleAddComment function.
 * This is the event handler for the `commentForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newCommentText.value`.
 * 3. If the text is empty, return.
 * 4. Create a new comment object: { author: 'Student', text: commentText }
 * (For this exercise, 'Student' is a fine hardcoded author).
 * 5. Add the new comment to the global `currentComments` array (in-memory only).
 * 6. Call `renderComments()` to refresh the list.
 * 7. Clear the `newCommentText` textarea.
 */
function handleAddComment(event) {
  event.preventDefault();
  const commentText = newCommentText.value.trim();
  if (commentText === '') return;
  
  const newComment = {
    author: 'Student', 
    text: commentText,
    timestamp: new Date().toISOString() // Optional: add timestamp
  };
  
  currentComments.push(newComment);
  renderComments();
  newCommentText.value = '';
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentAssignmentId` by calling `getAssignmentIdFromURL()`.
 * 2. If no ID is found, display an error and stop.
 * 3. `fetch` both 'assignments.json' and 'comments.json' (you can use `Promise.all`).
 * 4. Find the correct assignment from the assignments array using the `currentAssignmentId`.
 * 5. Get the correct comments array from the comments object using the `currentAssignmentId`.
 * Store this in the global `currentComments` variable.
 * 6. If the assignment is found:
 * - Call `renderAssignmentDetails()` with the assignment object.
 * - Call `renderComments()` to show the initial comments.
 * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
 * 7. If the assignment is not found, display an error.
 */
async function initializePage() {
  currentAssignmentId = getAssignmentIdFromURL();
  
  if (!currentAssignmentId) {
    assignmentTitle.textContent = 'Error: No assignment ID provided in URL.';
    return;
  }
  
  try {
    // For demo purposes, use sample data if JSON files don't exist
    let assignments = [];
    let commentsData = {};
    
    try {
      const [assignmentsRes, commentsRes] = await Promise.all([
        fetch('assignments.json'),
        fetch('comments.json')
      ]);
      
      if (assignmentsRes.ok) {
        assignments = await assignmentsRes.json();
      }
      
      if (commentsRes.ok) {
        commentsData = await commentsRes.json();
      }
    } catch (fetchError) {
      console.warn('Could not load JSON files, using sample data:', fetchError);
      // Fallback to sample data
      assignments = [
        { 
          id: 'asg_1', 
          title: 'HTML Basics', 
          dueDate: '2024-07-15', 
          description: 'Learn basic HTML tags and structure', 
          files: ['https://example.com/html-guide.pdf', 'https://example.com/tutorial.html']
        },
        { 
          id: 'asg_2', 
          title: 'CSS Styling', 
          dueDate: '2024-07-22', 
          description: 'Style web pages with CSS', 
          files: ['https://example.com/css-cheatsheet.pdf']
        }
      ];
      
      commentsData = {
        'asg_1': [
          { author: 'Student', text: 'Great assignment! Learned a lot about HTML structure.' },
          { author: 'Teacher', text: 'Remember to validate your HTML using the W3C validator.' }
        ],
        'asg_2': [
          { author: 'Student', text: 'CSS is challenging but fun!' }
        ]
      };
    }
    
    const assignment = assignments.find(a => a.id === currentAssignmentId);
    currentComments = commentsData[currentAssignmentId] || [];
    
    if (assignment) {
      renderAssignmentDetails(assignment);
      renderComments();
      
      if (commentForm) {
        commentForm.addEventListener('submit', handleAddComment);
      }
    } else {
      assignmentTitle.textContent = 'Error: Assignment not found.';
      assignmentDueDate.textContent = '';
      assignmentDescription.textContent = '';
    }
  } catch (error) {
    assignmentTitle.textContent = 'Error loading assignment data.';
    console.error('Error initializing page:', error);
  }
}

// --- Initial Page Load ---
initializePage();
