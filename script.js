$(document).ready(function() {
    let currentIndex = 0;
    const items = $('.carousel-item');
    const itemCount = items.length;

    function cycleItems() {
        const item = $('.carousel-item').eq(currentIndex);
        items.css('opacity', '0');  // Esconde todas as imagens
        item.css('opacity', '1');   // Mostra a imagem atual
    }

    function nextItem() {
        currentIndex += 1;
        if (currentIndex >= itemCount) {
            currentIndex = 0;
        }
        cycleItems();
    }

    setInterval(nextItem, 3000);  // Muda de imagem a cada 3 segundos
});
