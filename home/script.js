document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('search-button').addEventListener('click', function() {
        var searchText = document.getElementById('search-text').value;
        alert('Search for: ' + searchText); // Replace with actual search functionality
    });
});
