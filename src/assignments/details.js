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
let currentAssignment = null;
let currentComments = [];

// --- Element Selections ---
const assignmentTitle = document.getElementById('assignment-title');
const assignmentDueDate = document.getElementById('assignment-due-date');
const assignmentDescription = document.getElementById('assignment-description');
const assignmentFilesList = document.getElementById('assignment-files-list');
const commentList = document.getElementById('comment-list');
const commentForm = document.getElementById('comment-form');
const newCommentText = document.getElementById('new-comment-text');

// --- Functions ---

/**
 * Get assignment ID from URL query parameter
 */
function getAssignmentIdFromURL() {
  const queryString = window.location.search;
  const urlParams = new URLSearchParams(queryString);
  return urlParams.get('id');
}

/**
 * Render assignment details to the page
 */
function renderAssignmentDetails(assignment) {
  // Set basic assignment information
  assignmentTitle.textContent = assignment.title;
  assignmentDueDate.textContent = `Due: ${assignment.due_date || assignment.dueDate}`;
  assignmentDescription.textContent = assignment.description;
  
  // Clear and rebuild files list
  assignmentFilesList.innerHTML = '';
  
  // Handle files (could be array or JSON string)
  let files = [];
  if (assignment.files) {
    if (typeof assignment.files === 'string') {
      try {
        files = JSON.parse(assignment.files);
      } catch (e) {
        // If not valid JSON, treat as array or use as-is
        files = assignment.files.split(',').map(f => f.trim());
      }
    } else if (Array.isArray(assignment.files)) {
      files = assignment.files;
    }
  }
  
  if (files.length === 0) {
    const li = document.createElement('li');
    li.textContent = 'No files attached';
    li.className = 'no-files';
    assignmentFilesList.appendChild(li);
    return;
  }
  
  // Create file list items
  files.forEach(file => {
    const li = document.createElement('li');
    const a = document.createElement('a');
    
    // For demo purposes, if it doesn't look like a full URL, just show it as text
    if (typeof file === 'string' && (file.startsWith('http') || file.includes('.'))) {
      a.href = '#';
      a.textContent = file;
      a.onclick = (e) => {
        e.preventDefault();
        alert(`In a real application, this would download: ${file}`);
      };
      li.appendChild(a);
    } else {
      li.textContent = file;
    }
    
    assignmentFilesList.appendChild(li);
  });
}

/**
 * Create a comment element
 */
function createCommentElement(comment) {
  const article = document.createElement('article');
  article.className = 'comment';
  
  const textPara = document.createElement('p');
  textPara.className = 'comment-text';
  textPara.textContent = comment.text || comment.comment;
  
  const footer = document.createElement('footer');
  footer.className = 'comment-author';
  footer.textContent = `Posted by: ${comment.author}`;
  
  article.appendChild(textPara);
  article.appendChild(footer);
  return article;
}

/**
 * Render all comments
 */
function renderComments() {
  commentList.innerHTML = '';
  
  if (currentComments.length === 0) {
    const noCommentsMsg = document.createElement('p');
    noCommentsMsg.textContent = 'No comments yet. Be the first to comment!';
    noCommentsMsg.className = 'no-comments';
    commentList.appendChild(noCommentsMsg);
    return;
  }
  
  currentComments.forEach(comment => {
    const commentElement = createCommentElement(comment);
    commentList.appendChild(commentElement);
  });
}

/**
 * Handle adding a new comment
 */
function handleAddComment(event) {
  event.preventDefault();
  const commentText = newCommentText.value.trim();
  
  if (!commentText) {
    alert('Please enter a comment before posting.');
    return;
  }
  
  // Create new comment object
  const newComment = {
    author: 'Student',
    text: commentText,
    assignment_id: currentAssignmentId,
    timestamp: new Date().toISOString()
  };
  
  // Add to local array
  currentComments.push(newComment);
  
  // Re-render comments
  renderComments();
  
  // Clear form
  newCommentText.value = '';
  
  // Show success message
  const successMsg = document.createElement('div');
  successMsg.className = 'success-message';
  successMsg.textContent = 'Comment posted successfully!';
  commentForm.parentNode.insertBefore(successMsg, commentForm);
  
  setTimeout(() => {
    if (successMsg.parentNode) {
      successMsg.parentNode.removeChild(successMsg);
    }
  }, 3000);
  
  // In a real app, you would send this to the server:
  // fetch('api/comments.php', {
  //   method: 'POST',
  //   headers: { 'Content-Type': 'application/json' },
  //   body: JSON.stringify(newComment)
  // })
}

/**
 * Main initialization function
 */
async function initializePage() {
  // Get assignment ID from URL
  currentAssignmentId = getAssignmentIdFromURL();
  
  if (!currentAssignmentId) {
    assignmentTitle.textContent = 'Error: No Assignment Selected';
    assignmentDescription.textContent = 'Please go back to the assignments list and click on an assignment to view its details.';
    
    // Add helpful links
    const helpDiv = document.createElement('div');
    helpDiv.innerHTML = `
      <p>Try these links:</p>
      <ul>
        <li><a href="?id=asg_1">Assignment 1: HTML Basics</a></li>
        <li><a href="?id=asg_2">Assignment 2: CSS Styling</a></li>
        <li><a href="?id=asg_3">Assignment 3: JavaScript Events</a></li>
      </ul>
    `;
    document.querySelector('main').appendChild(helpDiv);
    return;
  }
  
  try {
    console.log(`Loading assignment: ${currentAssignmentId}`);
    
    // Try to load from PHP API first
    try {
      // Load assignment data from PHP API
      const assignmentRes = await fetch(`api.php?resource=assignments&id=${currentAssignmentId}`);
      
      if (assignmentRes.ok) {
        const assignmentData = await assignmentRes.json();
        if (assignmentData.success) {
          currentAssignment = assignmentData.data;
          
          // Load comments from PHP API
          const commentsRes = await fetch(`api.php?resource=comments&assignment_id=${currentAssignmentId}`);
          if (commentsRes.ok) {
            const commentsData = await commentsRes.json();
            if (commentsData.success) {
              currentComments = commentsData.data;
            } else {
              currentComments = [];
            }
          } else {
            currentComments = [];
          }
        } else {
          throw new Error(assignmentData.message);
        }
      } else {
        throw new Error(`Failed to load assignment: ${assignmentRes.status}`);
      }
      
    } catch (apiError) {
      console.log('PHP API not available, trying JSON files:', apiError.message);
      
      // Fall back to JSON files
      const [assignmentsRes, commentsRes] = await Promise.all([
        fetch('api/assignments.json'),
        fetch('api/comments.json')
      ]);
      
      if (!assignmentsRes.ok) {
        throw new Error(`Failed to load assignments: ${assignmentsRes.status}`);
      }
      
      const assignments = await assignmentsRes.json();
      const commentsData = commentsRes.ok ? await commentsRes.json() : {};
      
      // Find the specific assignment
      currentAssignment = assignments.find(a => a.id === currentAssignmentId);
      
      if (!currentAssignment) {
        throw new Error(`Assignment ${currentAssignmentId} not found`);
      }
      
      // Get comments for this assignment only
      currentComments = commentsData[currentAssignmentId] || [];
    }
    
    // Render the single assignment
    renderAssignmentDetails(currentAssignment);
    renderComments();
    
    // Add event listener for comment form
    commentForm.addEventListener('submit', handleAddComment);
    
    console.log(`Successfully loaded assignment: ${currentAssignment.title}`);
    
  } catch (error) {
    console.error('Error:', error);
    
    // Show error
    assignmentTitle.textContent = 'Error Loading Assignment';
    assignmentDueDate.textContent = '';
    assignmentDescription.textContent = error.message;
    assignmentFilesList.innerHTML = '';
    commentList.innerHTML = '';
    
    // Initialize with empty comments
    currentComments = [];
    commentForm.addEventListener('submit', handleAddComment);
  }
}

// Start the page
initializePage();