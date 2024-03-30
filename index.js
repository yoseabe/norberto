console.log("Hello World");

document.addEventListener("DOMContentLoaded", function () {
  // Get the width of an element with the class 'box'
  const navbar = document.querySelector(".navbar");
  const landingImage = document.querySelector(".landingImage");

  if (navbar && landingImage) {
    const heightNav = navbar.offsetHeight;
    landingImage.style.paddingTop = heightNav + "px";
  }
  getJsonData("pizzaTable", "Pizza Category");
  getJsonData("burgerTable", "Pizzabrötchen und Paninis");
  getJsonData("salatTable", "Pizzabrötchen und Paninis");
});

console.log("HELADAWNADWN");

function getJsonData(tableID, type) {
  fetch("menuCard.json")
    .then((response) => response.json())
    .then((data) => {
      const table = document.getElementById(tableID);

      for (i = 0; i < 3; i++) {
        const tr1 = document.createElement("tr");
        const nummer = document.createElement("td");
        const name = document.createElement("td");
        const zutaten = document.createElement("td");
        const preisS = document.createElement("td");
        const preisL = document.createElement("td");
        if (nummer && name && zutaten && preisS && preisL) {
          nummer.textContent = data[type][i]["nummer"];
          name.textContent = data[type][i]["name"];
          zutaten.textContent = data[type][i]["zutaten"];
          preisS.textContent = data[type][i]["priceS"];
          preisL.textContent = data[type][i]["priceL"];
          tr1.appendChild(nummer);
          tr1.appendChild(name);
          tr1.appendChild(zutaten);
          tr1.appendChild(preisS);
          tr1.appendChild(preisL);
          table.querySelector("tbody").appendChild(tr1);
        }
      }
    });
}
