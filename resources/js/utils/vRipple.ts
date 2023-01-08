export const vRipple = {
    mounted(el) {
        el.addEventListener('click', (evt) => {
            const { offsetLeft, offsetTop } = el;
            const {clientY, clientX } = evt;
            const ripples = document.createElement('span');
            const x = clientX - offsetLeft;
            const y = clientY - offsetTop;
            ripples.className = `left-[${x}px] top-[${y}px] absolute bg-white -translate-x-1/2 -translate-y-1/2 rounded-full animate-ripple`
            el.appendChild(ripples);
            setTimeout(() => {
                ripples.remove()
            }, 1000)

        })
    },
}