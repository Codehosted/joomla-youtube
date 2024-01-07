document.addEventListener("DOMContentLoaded", function () {
  function replaceImageWithIframe(event) {
    let target = event.target;

    // if the event target is the svg element, get the parent button
    if (target.tagName === 'svg' || target.tagName === 'path') {
      target = target.closest('button');
    }

    const iframe = target.getAttribute("data-youtube");
    const videoId = target.getAttribute("data-video-id");

    if (iframe) {
      // parse the iframe string into a DOM element
      const iframeElement = document.createRange().createContextualFragment(iframe);
      const thumbnail = document.getElementById(`thumbnail-${videoId}`);
      document.getElementById(`button-${videoId}`).remove();
      
      iframeElement.querySelector("iframe").setAttribute("width", thumbnail.offsetWidth);
      iframeElement.querySelector("iframe").setAttribute("height", thumbnail.offsetHeight);
      thumbnail.replaceWith(iframeElement);      
    }
  }

  const imagesWithYtFrame = document.querySelectorAll("img[data-video-id]");
  imagesWithYtFrame.forEach((image) => {
    image.addEventListener("click", replaceImageWithIframe);
  });


  const buttonsWithYtFrame = document.querySelectorAll("button[data-video-id]");
  buttonsWithYtFrame.forEach((button) => {
    button.addEventListener("click", replaceImageWithIframe);
  });

});
