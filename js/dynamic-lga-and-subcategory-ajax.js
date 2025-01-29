//populate LGA dynamically
document.addEventListener('DOMContentLoaded', function () 
{
    const stateDropdown = document.getElementById("state");
    
    stateDropdown.addEventListener("change", function () {
        fetchLgas(this.value);
    });

    function fetchLgas(stateId) 
    {
        const lgaDropdown = document.getElementById("lga");

        if (!stateId) 
        {
            lgaDropdown.innerHTML = '<option value="">---Select LGA---</option>';
            return;
        }

        fetch(`select-lgas.php?state_id=${stateId}`)
        .then(response => response.text())
        .then(data => {
            lgaDropdown.innerHTML = '<option value="">---Select LGA---</option>';
            lgaDropdown.innerHTML += data;
        })
        .catch(error => {
            lgaDropdown.innerHTML = '<option value="">---Error loading LGA---</option>';
        });
    }

});


//populate subcategories dynamically
document.addEventListener('DOMContentLoaded', function () 
{
    // Fetch subcategories
    const categoryDropdown = document.getElementById('category');

    categoryDropdown.addEventListener('change', function () {
        fetchSubcategories(this.value);
    });

    function fetchSubcategories(categoryId) 
    {
        const subcategoryDropdown = document.getElementById('subcategory');

        if (!categoryId) 
        {
            subcategoryDropdown.innerHTML = '<option value="">---Select Subcategory---</option>';
            return;
        }

        fetch(`select-subcategories.php?category_id=${categoryId}`)
        .then(response => response.text())
        .then(data => {
            subcategoryDropdown.innerHTML = '<option value="">---Select Subcategory---</option>';
            subcategoryDropdown.innerHTML += data;
        })
        .catch(error => {
            subcategoryDropdown.innerHTML = '<option value="">---Error loading subcategories---</option>';
        });
    }

});
