var passwordInput = document.getElementById('password');
var eyesicon = document.querySelector('.toggle-pw i')
    
function togglePassword() {
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eyesicon.classList.remove("fa-eye-slash");
        eyesicon.classList.add("fa-eye");
    } else {
        passwordInput.type = "password";
        eyesicon.classList.remove("fa-eye");
        eyesicon.classList.add("fa-eye-slash");
    }
}

eyesicon.addEventListener("click" , togglePassword);