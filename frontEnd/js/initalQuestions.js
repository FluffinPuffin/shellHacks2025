document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("initialQuestions");
    const steps = form.querySelectorAll("div[class^='step-']");
    const nextBtn = form.querySelector("input[name='Next']");
    const backBtn = form.querySelector("input[name='back']");

    let currentStep = 0;

    function showStep(index) {
        steps.forEach((step, i) => {
            step.style.display = i === index ? "block" : "none";
        });

        backBtn.style.display = index > 0 ? "inline-block" : "none";
        nextBtn.style.display = index < steps.length - 1 ? "inline-block" : "none";
    }

    nextBtn.addEventListener("click", () => {
        if (currentStep < steps.length - 1) {
            currentStep++;
            showStep(currentStep);
        }
    });

    backBtn.addEventListener("click", () => {
        if (currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
    });

    showStep(currentStep);
});
