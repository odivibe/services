    const questions = document.querySelectorAll('.faq-question');

    questions.forEach(question => {
        question.addEventListener('click', () => {
            const answer = question.nextElementSibling;
            const isActive = question.classList.contains('active');

            // Close all answers
            questions.forEach(q => {
                q.classList.remove('active');
                q.nextElementSibling.style.display = 'none';
            });

            // Toggle current answer
            if (!isActive) {
                question.classList.add('active');
                answer.style.display = 'block';
            }
        });
    });