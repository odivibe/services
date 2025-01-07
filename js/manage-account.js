// This script will manage account like change password, email, phone, etc

const links = document.querySelectorAll('.sidebar a'); // All sidebar links
const contentArea = document.getElementById('sidebar-display-area');

 links.forEach(link => 
 {
    link.addEventListener('click', function (event) 
    {
        event.preventDefault();
        const pageName = this.dataset.page; // Get the "data-page" attribute, which is the same as the file name

        fetch(`../account/${pageName}.php`) // Load corresponding page file
            .then(response => response.text())
            .then(html => {
                contentArea.innerHTML = html;
            })
            .catch(error => {
                contentArea.innerHTML = '<p>Error loading content. Please try again.</p>';
            });
    });
});