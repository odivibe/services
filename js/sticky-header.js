window.addEventListener('scroll', function() {
    var header = document.getElementById('nav-header');
    if (window.pageYOffset > 250) 
    {
        header.classList.add('sticky');
    } 
    else 
    {
        header.classList.remove('sticky');
    }
});
