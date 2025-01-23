    function changeImage(src)
    {
        document.getElementById('featured-image').src = src;

    }

    // Update star rating dynamically
    window.onload = function () {
        const averageRating = <?php echo $average_rating; ?>; // PHP value
        const starsInner = document.querySelector('.stars-inner');
        
        // Calculate percentage for filled stars
        const ratingPercentage = (averageRating / 5) * 100;
        
        // Apply width to display stars based on the rating
        starsInner.style.width = ratingPercentage + '%';
    };


    // Function to scroll the gallery horizontally by a set amount
    function changeImageByIndex(direction) 
    {
        const gallery = document.querySelector('.thumbnail-gallery');
        const itemWidth = document.querySelector('.thumbnail-item').offsetWidth + 10; // Item width + margin
        const scrollAmount = itemWidth * 5; // Scroll by the width of 5 items (visible at once)

        if (direction === 'prev') 
        {
            gallery.scrollLeft -= scrollAmount; // Scroll left by 5 items
            //alert(1)
        } 
        else if (direction === 'next') 
        {
            gallery.scrollLeft += scrollAmount; // Scroll right by 5 items
            //alert(2)
        }
    }
