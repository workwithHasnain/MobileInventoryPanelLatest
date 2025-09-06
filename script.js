var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});


    function toggleMenu() {
        let menu = document.getElementById('leftMenu');
        if (menu.style.display === 'block') {
            menu.style.display = 'none';
        } else {
            menu.style.display = 'block';
        }
    }
    

// let lastScrollTop = 0;
// const navbar = document.getElementById("navbar");

// window.addEventListener("scroll", function () {
//     let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

//     if (scrollTop > lastScrollTop) {
//         // Scroll Down - hide navbar
//         navbar.style.transform = "translateY(-100%)";
//     } else {
//         // Scroll Up - show navbar
//         navbar.style.transform = "translateY(0)";
//     }

//     lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
// });
var prevScrollpos = window.pageYOffset;
window.onscroll = function () {
    var currentScrollPos = window.pageYOffset;
    if (prevScrollpos > currentScrollPos) {
        document.getElementById("navbar").style.top = "0";
    } else {
        document.getElementById("navbar").style.top = "-130px";
    }
    prevScrollpos = currentScrollPos;
}