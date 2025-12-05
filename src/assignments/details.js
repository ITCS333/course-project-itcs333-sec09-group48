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
// details.js - Show ALL assignments with their comments
let allAssignments = [];
let allComments = {};

// Element selections
const pageTitle = document.getElementById('page-title');
const assignmentsContainer = document.getElementById('assignments-container');

/**
 * Create HTML for a single assignment with its comments
 */
function createAssignmentSection(assignment) {
    const section = document.createElement('section');
    section.className = 'assignment-section';
    section.id = `assignment-${assignment.id}`;
    
    // Assignment header
    const header = document.createElement('div');
    header.className = 'assignment-header';
    
    const title = document.createElement('h2');
    title.textContent = assignment.title;
    
    const dueDate = document.createElement('p');
    dueDate.className = 'due-date';
    dueDate.textContent = `Due: ${assignment.dueDate}`;
    
    const description = document.createElement('p');
    description.className = 'description';
    description.textContent = assignment.description;
    
    // Files list
    const filesTitle = document.createElement('h3');
    filesTitle.textContent = 'Attached Files:';
    
    const filesList = document.createElement('ul');
    filesList.className = 'files-list';
    
    if (assignment.files && assignment.files.length > 0) {
        assignment.files.forEach(file => {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = '#';
            a.textContent = file;
            a.onclick = (e) => {
                e.preventDefault();
                alert(`Would download: ${file}`);
            };
            li.appendChild(a);
            filesList.appendChild(li);
        });
    } else {
        const li = document.createElement('li');
        li.textContent = 'No files attached';
        filesList.appendChild(li);
    }
    
    // Comments section for this assignment
    const commentsSection = document.createElement('div');
    commentsSection.className = 'comments-section';
    
    const commentsTitle = document.createElement('h3');
    commentsTitle.textContent = 'Questions & Discussion:';
    
    const commentsList = document.createElement('div');
    commentsList.className = 'comments-list';
    commentsList.id = `comments-${assignment.id}`;
    
    // Comment form for this assignment
    const commentForm = document.createElement('form');
    commentForm.className = 'comment-form';
    commentForm.dataset.assignmentId = assignment.id;
    
    const fieldset = document.createElement('fieldset');
    const legend = document.createElement('legend');
    legend.textContent = 'Add a Comment';
    
    const textarea = document.createElement('textarea');
    textarea.placeholder = 'Type your question or comment here...';
    textarea.required = true;
    
    const submitButton = document.createElement('button');
    submitButton.type = 'submit';
    submitButton.textContent = 'Post Comment';
    
    fieldset.appendChild(legend);
    fieldset.appendChild(textarea);
    fieldset.appendChild(submitButton);
    commentForm.appendChild(fieldset);
    
    // Assemble everything
    header.appendChild(title);
    header.appendChild(dueDate);
    header.appendChild(description);
    
    commentsSection.appendChild(commentsTitle);
    commentsSection.appendChild(commentsList);
    commentsSection.appendChild(commentForm);
    
    section.appendChild(header);
    section.appendChild(filesTitle);
    section.appendChild(filesList);
    section.appendChild(commentsSection);
    
    return section;
}

/**
 * Render comments for a specific assignment
 */
function renderCommentsForAssignment(assignmentId) {
    const commentsList = document.getElementById(`comments-${assignmentId}`);
    if (!commentsList) return;
    
    commentsList.innerHTML = '';
    
    const comments = allComments[assignmentId] || [];
    
    if (comments.length === 0) {
        const noComments = document.createElement('p');
        noComments.className = 'no-comments';
        noComments.textContent = 'No comments yet. Be the first to comment!';
        commentsList.appendChild(noComments);
        return;
    }
    
    comments.forEach(comment => {
        const commentElement = createCommentElement(comment);
        commentsList.appendChild(commentElement);
    });
}

/**
 * Create a single comment element
 */
function createCommentElement(comment) {
    const article = document.createElement('article');
    article.className = 'comment';
    
    const text = document.createElement('p');
    text.className = 'comment-text';
    text.textContent = comment.text;
    
    const author = document.createElement('footer');
    author.className = 'comment-author';
    author.textContent = `Posted by: ${comment.author}`;
    
    article.appendChild(text);
    article.appendChild(author);
    
    return article;
}

/**
 * Handle adding a new comment to a specific assignment
 */
function handleAddComment(event, assignmentId) {
    event.preventDefault();
    
    const form = event.target;
    const textarea = form.querySelector('textarea');
    const commentText = textarea.value.trim();
    
    if (!commentText) {
        alert('Please enter a comment before posting.');
        return;
    }
    
    // Create new comment
    const newComment = {
        author: 'Student',
        text: commentText,
        timestamp: new Date().toISOString()
    };
    
    // Add to comments data
    if (!allComments[assignmentId]) {
        allComments[assignmentId] = [];
    }
    allComments[assignmentId].push(newComment);
    
    // Re-render comments for this assignment
    renderCommentsForAssignment(assignmentId);
    
    // Clear form
    textarea.value = '';
    
    // Show success message
    const successMsg = document.createElement('div');
    successMsg.className = 'success-message';
    successMsg.textContent = 'Comment posted successfully!';
    form.parentNode.insertBefore(successMsg, form);
    
    setTimeout(() => {
        if (successMsg.parentNode) {
            successMsg.parentNode.removeChild(successMsg);
        }
    }, 3000);
}

/**
 * Render all assignments
 */
function renderAllAssignments() {
    assignmentsContainer.innerHTML = '';
    
    if (allAssignments.length === 0) {
        assignmentsContainer.innerHTML = '<p>No assignments found.</p>';
        return;
    }
    
    allAssignments.forEach(assignment => {
        const assignmentSection = createAssignmentSection(assignment);
        assignmentsContainer.appendChild(assignmentSection);
        
        // Add event listener to the form for this assignment
        const form = assignmentSection.querySelector('.comment-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                handleAddComment(e, assignment.id);
            });
        }
        
        // Render comments for this assignment
        renderCommentsForAssignment(assignment.id);
    });
}

/**
 * Main initialization function
 */
async function initializePage() {
    try {
        console.log('Loading all assignments and comments...');
        
        // Fetch all data
        const [assignmentsRes, commentsRes] = await Promise.all([
            fetch('api/assignments.json'),
            fetch('api/comments.json')
        ]);
        
        if (!assignmentsRes.ok) {
            throw new Error(`Failed to load assignments: ${assignmentsRes.status}`);
        }
        
        allAssignments = await assignmentsRes.json();
        console.log('Loaded assignments:', allAssignments);
        
        // Load comments if available
        if (commentsRes.ok) {
            allComments = await commentsRes.json();
            console.log('Loaded comments:', allComments);
        } else {
            console.log('No comments file found, starting with empty comments');
            allComments = {};
        }
        
        // Initialize empty comments for assignments that don't have any
        allAssignments.forEach(assignment => {
            if (!allComments[assignment.id]) {
                allComments[assignment.id] = [];
            }
        });
        
        // Render everything
        renderAllAssignments();
        
        console.log('Page initialized successfully!');
        
    } catch (error) {
        console.error('Error loading data:', error);
        
        // Show fallback data for testing
        assignmentsContainer.innerHTML = `
            <div class="error">
                <h2>Error Loading Data</h2>
                <p>${error.message}</p>
                <p>Using sample data for demonstration...</p>
            </div>
        `;
        
        // Use sample data
        allAssignments = [
            {
                id: "asg_1",
                title: "Assignment 1: HTML Basics",
                description: "Create a semantic HTML structure for a personal portfolio. Focus on using tags like <header>, <nav>, <main>, <article>, and <footer>.",
                dueDate: "2025-11-10",
                files: ["portfolio-requirements.pdf", "examples.zip"]
            },
            {
                id: "asg_2",
                title: "Assignment 2: CSS Styling",
                description: "Style your HTML portfolio using modern CSS. You must use Flexbox or Grid for layout and include at least one CSS animation.",
                dueDate: "2025-11-17",
                files: ["style-guide.pdf"]
            },
            {
                id: "asg_3",
                title: "Assignment 3: JavaScript Events",
                description: "Make your portfolio interactive. Add event listeners to create a modal window for your projects and a theme switcher (light/dark mode).",
                dueDate: "2025-11-24",
                files: ["js-requirements.pdf", "event-listeners-guide.txt"]
            }
        ];
        
        allComments = {
            "asg_1": [
                { author: "Fatema Ahmed", text: "Is it okay to use a 'section' inside an 'article'?" },
                { author: "Mohamed Abdulla", text: "Can we use a CSS framework for this first assignment?" }
            ],
            "asg_2": [
                { author: "Noora Salman", text: "Having trouble with Flexbox. Any good tutorials?" }
            ],
            "asg_3": []
        };
        
        renderAllAssignments();
    }
}

// Start the page
initializePage();