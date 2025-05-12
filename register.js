var passwordInput = document.getElementById('password');
var confirmInput = document.getElementById('confirm');
var eyesPW = document.querySelector('.toggle-pw i');
var eyesCF = document.querySelector('.toggle-cf i');
var thumbnailinput = document.getElementById('upload');
    
function togglePassword() {
    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eyesPW.classList.remove("fa-eye-slash");
        eyesPW.classList.add("fa-eye");
    } else {
        passwordInput.type = "password";
        eyesPW.classList.remove("fa-eye");
        eyesPW.classList.add("fa-eye-slash");
    }
}

function toggleConfirm() {
    if (confirmInput.type === "password") {
        confirmInput.type = "text";
        eyesCF.classList.remove("fa-eye-slash");
        eyesCF.classList.add("fa-eye");
    } else {
        confirmInput.type = "password";
        eyesCF.classList.remove("fa-eye");
        eyesCF.classList.add("fa-eye-slash");
    }   
}

function showThumbnail() {
    var file = thumbnailinput.files[0];
    var reader = new FileReader();
    var preview = document.getElementById('preview');
    preview.innerHTML = ""; // Clear previous images
    
    reader.onload = function(event) {                
        var image = document.createElement('img');
        image.src = event.target.result;            // file reader 讀取的結果 (data url), event.target是trigger event的物件 (FileReader)
        image.style.width = "80px";               // set the width of the image
        image.style.height = "80px";             // set the height of the image
        image.style.borderRadius = "10px";         // set the border radius to make it circular
        image.style.marginTop = "5px";            // set the margin top to 10px
        preview.appendChild(image);                 // append the image to the preview div
    }
    // 寫在onload後面 當read完之後才知道要做什麼
    reader.readAsDataURL(file);                     // read the file as a data URL

}

eyesPW.addEventListener("click" , togglePassword);
eyesCF.addEventListener("click" , toggleConfirm);
thumbnailinput.addEventListener("change", showThumbnail);