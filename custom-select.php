<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>custom select</title>

        <style>
            .custom-select {
                position: relative;
                width: 300px;
                font-family: Arial, sans-serif;
            }

            .select-selected {
                background-color: #f9f9f9;
                border: 1px solid #ccc;
                padding: 10px;
                cursor: pointer;
                user-select: none;
                border-radius: 4px;
            }

            .select-selected:hover {
                background-color: #e6e6e6;
            }

            .select-dropdown {
                display: none;
                position: absolute;
                background-color: #fff;
                border: 1px solid #ccc;
                border-radius: 4px;
                z-index: 1000;
                width: 100%;
                max-height: 150px;
                overflow-y: auto;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }

            .select-item {
                padding: 10px;
                cursor: pointer;
                user-select: none;
            }

            .select-item:hover {
                background-color: #f1f1f1;
            }

            .select-item.selected {
                background-color: #007bff;
                color: #fff;
            }

        </style>
    </head>
    <body>

    <div class="custom-select">
        <div class="select-selected" onclick="toggleDropdown()">Select Category</div>
        <div class="select-dropdown">
            <div class="select-item" onclick="selectItem(this)" data-value="1">Category 1</div>
            <div class="select-item" onclick="selectItem(this)" data-value="2">Category 2</div>
            <div class="select-item" onclick="selectItem(this)" data-value="3">Category 3</div>
        </div>
    </div>
    <input type="hidden" id="custom-select-value" name="category" value="">

        <script>
            function toggleDropdown() 
            {
                const dropdown = document.querySelector('.select-dropdown');
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            }

            function selectItem(element) 
            {
                const selected = document.querySelector('.select-selected');
                const dropdown = document.querySelector('.select-dropdown');
                const inputValue = document.getElementById('custom-select-value');

                // Set the selected value
                selected.textContent = element.textContent;
                inputValue.value = element.dataset.value;

                // Mark the selected item
                document.querySelectorAll('.select-item').forEach(item => item.classList.remove('selected'));
                element.classList.add('selected');

                // Close the dropdown
                dropdown.style.display = 'none';
            }

            // Close dropdown if clicked outside
            document.addEventListener('click', function (event) {
                const dropdown = document.querySelector('.select-dropdown');
                const selected = document.querySelector('.select-selected');
                if (!selected.contains(event.target) && !dropdown.contains(event.target)) {
                    dropdown.style.display = 'none';
                }
            });



            //implementation
            function populateDropdown(categories) 
            {
                const dropdown = document.querySelector('.select-dropdown');
                dropdown.innerHTML = ''; // Clear existing options

                categories.forEach(category => {
                    const item = document.createElement('div');
                    item.className = 'select-item';
                    item.textContent = category.name;
                    item.dataset.value = category.id;
                    item.onclick = () => selectItem(item);
                    dropdown.appendChild(item);
                });
            }

            // Example categories array
            const categories = [
                { id: '1', name: 'Category 1' },
                { id: '2', name: 'Category 2' },
                { id: '3', name: 'Category 3' }
            ];

            // Populate the dropdown
            populateDropdown(categories);


        </script>

    </body>
</html>