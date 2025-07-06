document.addEventListener('DOMContentLoaded', function() {
  // Mobile Menu Toggle
  const hamburger = document.querySelector('.hamburger');
  const menu = document.querySelector('.menu');
  
  hamburger.addEventListener('click', function() {
    this.classList.toggle('active');
    menu.classList.toggle('active');
  });
  
  // Close menu when clicking on a link
  const menuLinks = document.querySelectorAll('.menu a');
  menuLinks.forEach(link => {
    link.addEventListener('click', () => {
      hamburger.classList.remove('active');
      menu.classList.remove('active');
    });
  });
  
  // Product Color Switching
  document.querySelectorAll('.product-card').forEach(card => {
    const preview = card.querySelector('.product-image');
    if (!preview) return;
    
    const name = preview.id.replace('preview-', '');
    const colorRadios = card.querySelectorAll('.colornav input');
    
    colorRadios.forEach(radio => {
      radio.addEventListener('change', () => {
        preview.src = `assets/image/iphone/${name}-${radio.value}.png`;
        preview.alt = `${preview.alt.split(' ')[0]} ${radio.nextElementSibling.textContent}`;
      });
    });
  });
  
  // Smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      
      const targetId = this.getAttribute('href');
      if (targetId === '#') return;
      
      const targetElement = document.querySelector(targetId);
      if (targetElement) {
        window.scrollTo({
          top: targetElement.offsetTop - 80,
          behavior: 'smooth'
        });
      }
    });
  });
  
  // Preload product images for better user experience
  function preloadImages() {
    const products = [
      'iphone16', 
      'iphone16-promax', 
      'iphone16-pro', 
      'iphone16e'
    ];
    
    const colors = [
      'black', 
      'silver', 
      'white', 
      'desert', 
      'blue', 
      'titanium', 
      'starlight', 
      'red'
    ];
    
    products.forEach(product => {
      colors.forEach(color => {
        new Image().src = `assets/image/iphone/${product}-${color}.png`;
      });
    });
  }
  
  // Start preloading after page loads
  setTimeout(preloadImages, 1000);
});