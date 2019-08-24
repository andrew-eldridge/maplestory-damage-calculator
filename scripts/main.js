let banner = document.getElementsByClassName("banner")[0];
let backgroundImage = document.getElementsByClassName("background-image")[0];
let form = document.getElementsByTagName("form")[0];
let returnBtn = document.getElementsByClassName("return")[0];
let bossingElements = document.getElementsByClassName("bossing");
let mobbingElements = document.getElementsByClassName("mobbing");
let mobbingRadio = document.getElementById("calculation-mode1");
let bossingRadio = document.getElementById("calculation-mode2");
let bossingInputs = document.getElementsByClassName("bossing-input");
let mobbingInputs = document.getElementsByClassName("mobbing-input");

setTimeout(function(){
    banner.style.padding = "0px";
    banner.style.height = "0px";
    banner.innerHTML = "";
}, 5000);

function fadeIn(hasPost, hasIncompleteData) {

    // Organize class types
    organizeSelect("#class-type");

	if (hasPost) {
		let submit = document.getElementsByClassName("submit")[0];
		submit.style.display = "none";
		returnBtn.style.display = "inline-block";
		let fieldsets = document.getElementsByTagName("fieldset");
		for (let i=0; i<fieldsets.length; i++) {
			if (fieldsets[i].classList.contains("results")) {
				fieldsets[i].style.display = "block";
			} else {
				fieldsets[i].style.display = "none";
			}
		}
		for (let i=0; i<bossingElements.length; i++) {
			bossingElements[i].style.display = "none";
		}
		for (let i=0; i<mobbingElements.length; i++) {
			mobbingElements[i].style.display = "none";
		}
	} else if (hasIncompleteData) {
        banner.style.backgroundColor = "#ff3642";
	}
	backgroundImage.style.opacity = "0";
	form.style.opacity = "1";

}

function enableClassSelect(classType) {

	// Declare classes by type
	const magicians = ["Battle Mage", "Beast Tamer", "Blaze Wizard", "Evan", "Kanna", "Luminous", "Bishop", "Ice/Lightning Mage", "Fire/Poison Mage", "Kinesis", "Illium"];
	const thieves = ["Dual Blade", "Night Walker", "Phantom", "Shadower", "Night Lord", "Xenon", "Cadena"];
	const warriors = ["Aran", "Dawn Warrior", "Demon Avenger", "Demon Slayer", "Hayato", "Kaiser", "Mihile", "Dark Knight", "Hero", "Paladin", "Zero", "Blaster"];
	const bowmen = ["Marksman", "Bowmaster", "Wild Hunter", "Wind Archer", "Mercedes", "Pathfinder"];
	const pirates = ["Angelic Buster", "Cannoneer", "Jett", "Mechanic", "Buccaneer", "Corsair", "Shade", "Thunder Breaker", "Ark"];

	// Map classes to their type
	const classTypesNames = ["Magician", "Thief", "Warrior", "Bowman", "Pirate"];
	const classTypes = [magicians, thieves, warriors, bowmen, pirates];

	// Get class select input
	let classSelect = document.getElementById("class");

	// Clear all options except default option
	let length = classSelect.options.length;
	if (length > 1) {
		for (let i = classSelect.options.length - 1; i > 0; i--) {
			classSelect.remove(i);
		}
	}

	// Determine which class type was selected
	for (let i=0; i<classTypes.length; i++) {
		if (classType === classTypesNames[i]) {
			// Create an option for each class of the correct type
			let classes = classTypes[i];
			for (let j=0; j<classes.length; j++) {
				let opt = document.createElement("option");
				opt.value = classes[j];
				opt.innerHTML = classes[j];
				classSelect.appendChild(opt);
			}
			break;
		}
	}

	// Enable the class selection input
	classSelect.disabled = false;
	classSelect.classList.remove("disabled-select");

	// Organize class items
    organizeSelect("#class");

}

function organizeSelect(selectId) {
    let sel = $(selectId);
    let selected = sel.val();
    let opts_list = sel.find('option');
    opts_list.sort(function(a, b) { return $(a).text() > $(b).text() ? 1 : -1; });
    sel.html('').append(opts_list);
    sel.val(selected);
}

function redirect(location) {
	window.location.replace(location);
}

function updateCalculationMode() {
	console.log(bossingElements.type);
	console.log(mobbingElements);
	if (mobbingRadio.checked) {
		for (let i=0; i<bossingElements.length; i++) {
			bossingElements[i].style.display = "none";
		}
		for (let i=0; i<bossingInputs.length; i++) {
			bossingInputs[i].required = false;
		}
		for (let i=0; i<mobbingElements.length; i++) {
			mobbingElements[i].style.display = "block";
		}
		for (let i=0; i<mobbingInputs.length; i++) {
			mobbingInputs[i].required = true;
		}
		console.log("Calculation mode changed to mobbing.");
	} else if (bossingRadio.checked) {
		for (let i=0; i<bossingElements.length; i++) {
			bossingElements[i].style.display = "block";
		}
		for (let i=0; i<bossingInputs.length; i++) {
			bossingInputs[i].required = true;
		}
		for (let i=0; i<mobbingElements.length; i++) {
			mobbingElements[i].style.display = "none";
		}
		for (let i=0; i<mobbingInputs.length; i++) {
			mobbingInputs[i].required = false;
		}
		console.log("Calculation mode changed to bossing.");
	} else {
		console.log("Error: Invalid calculation mode selected.");
	}
}

function updateInputMode() {
	let decimalRadio = document.getElementById("input-mode1");
	let percentageRadio = document.getElementById("input-mode2");
	let inputs = document.getElementsByTagName("input");
	if (decimalRadio.checked) {
		for (let i=0; i<inputs.length; i++) {
			if ((inputs[i].type !== "number") || (inputs[i].classList.contains("range"))) {
				continue;
			}
			inputs[i].min = "0";
			inputs[i].max = "5";
			inputs[i].step = "0.01";
		}
		console.log("Input mode changed to decimal.");
	} else if (percentageRadio.checked) {
		for (let i=0; i<inputs.length; i++) {
			if ((inputs[i].type !== "number") || (inputs[i].classList.contains("range"))) {
				continue;
			}
			inputs[i].min = "0";
			inputs[i].max = "500";
			inputs[i].step = "1";
		}
		console.log("Input mode changed to percentage.");
	} else {
		console.log("Error: Invalid input mode selected.");
	}
}

function validate(id) {
	if (id === "all") {
		let inputs = document.getElementsByTagName("input");
		for (let i=0; i<inputs.length; i++) {
			if (inputs[i].type !== "number") {
				continue;
			}
			if (inputs[i].checkValidity()) {
				if (inputs[i].value !== "") {
					inputs[i].style.backgroundColor = "#BBFF9E";
				}
			} else {
				if (inputs[i].value !== "") {
					inputs[i].style.backgroundColor = "#FFAEAE";
				}
			}
		}
		return;
	}
	let targetElement = document.getElementById(id);
	if (targetElement.value !== "") {
		if (targetElement.checkValidity()) {
			targetElement.style.backgroundColor = "#BBFF9E";
		} else {
			targetElement.style.backgroundColor = "#FFAEAE";
		}
	}
}

function toggleDetails() {
	let details = document.getElementsByTagName("pre")[0];
	let detailsToggle = document.getElementsByClassName("details-toggle")[0];
	if (details.style.display === "none") {
		details.style.display = "block";
		detailsToggle.innerHTML = "Hide Details";
	} else {
		details.style.display = "none";
		detailsToggle.innerHTML = "Show Details";
	}
}
