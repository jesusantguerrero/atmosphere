export const usePrint = (elementId?: string) => {
    const print = (element = elementId, {
      beforePrint,
      delay
    } = {
      beforePrint: () => {},
      delay: 0
    }, title?: string) => {
      const modalInvoice = document.getElementById(element)
      const cloned = modalInvoice?.cloneNode(true)
      let section = document.getElementById("print")
      const $title = document.querySelector('title');
      const oldTitle = $title?.text;

      if ($title) {
        $title.textContent = title ?? oldTitle;
      }

      if (!section) {
         section  = document.createElement("div")
         section.id = "print"
         document.body.appendChild(section)
      }

      section.innerHTML = "";
      if (cloned) {
        section.appendChild(cloned);
        beforePrint()
        setTimeout(() => {
          window.print();
          if ($title) {
            $title.textContent = oldTitle;
          }
        }, delay)
      }
    }

    return {
      customPrint: print
    }
  }
