const menuToggle = document.getElementById("menuToggle");
const mainNav = document.getElementById("mainNav");
menuToggle.addEventListener("click", () => {
  const open = mainNav.classList.toggle("open");
  menuToggle.setAttribute("aria-expanded", String(open));
});
mainNav.querySelectorAll("a").forEach(link => link.addEventListener("click", () => {
  mainNav.classList.remove("open");
  menuToggle.setAttribute("aria-expanded", "false");
}));

const backdrop = document.getElementById("modalBackdrop");
const modalTitle = document.getElementById("modalTitle");
const modalDescription = document.getElementById("modalDescription");
const form = document.getElementById("accountForm");
const formMessage = document.getElementById("formMessage");
function openModal(type) {
  const login = type === "login";
  modalTitle.textContent = login ? "Welcome back" : "Create your account";
  modalDescription.textContent = login ? "Log in to continue to your HomeBid account." : "Join HomeBid and discover your next home.";
  form.querySelector(".form-submit").textContent = login ? "Log In" : "Create Account";
  form.querySelector('label:first-child').style.display = login ? "none" : "grid";
  formMessage.textContent = "";
  backdrop.classList.add("open");
  backdrop.setAttribute("aria-hidden", "false");
  document.body.style.overflow = "hidden";
}
function closeModal() {
  backdrop.classList.remove("open");
  backdrop.setAttribute("aria-hidden", "true");
  document.body.style.overflow = "";
}
document.querySelectorAll("[data-modal]").forEach(button => button.addEventListener("click", () => openModal(button.dataset.modal)));
document.getElementById("modalClose").addEventListener("click", closeModal);
backdrop.addEventListener("click", event => { if (event.target === backdrop) closeModal(); });
document.addEventListener("keydown", event => { if (event.key === "Escape") closeModal(); });
form.addEventListener("submit", event => {
  event.preventDefault();
  formMessage.textContent = "Demo interaction only — connect your authentication service to create an account.";
});

let toastTimer;
function showToast(message) {
  const toast = document.getElementById("toast");
  toast.textContent = message;
  toast.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove("show"), 2800);
}
document.querySelectorAll(".view-property").forEach(button => button.addEventListener("click", () => {
  if (button.dataset.property === "Cebu Skyline Townhouse") window.location.href = "property.html";
  else showToast(`${button.dataset.property} — its detail page can be added next.`);
}));


// Property page auction countdown (only runs when the countdown element exists).
let seconds=3*86400+5*3600+18*60;const timer=document.getElementById('countdown');function tick(){if(!timer)return;const d=Math.floor(seconds/86400),h=Math.floor(seconds%86400/3600),m=Math.floor(seconds%3600/60);timer.textContent=`${String(d).padStart(2,'0')}d : ${String(h).padStart(2,'0')}h : ${String(m).padStart(2,'0')}m`;if(seconds>0)seconds--}tick();setInterval(tick,60000);

