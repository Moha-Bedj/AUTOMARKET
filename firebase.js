import { initializeApp }
  from "https://www.gstatic.com/firebasejs/10.7.0/firebase-app.js";
import { getAuth, signInWithPopup,
         GoogleAuthProvider, FacebookAuthProvider }
  from "https://www.gstatic.com/firebasejs/10.7.0/firebase-auth.js";

const firebaseConfig = {
  apiKey: "AIzaSyB2ShuoAsD7xRQq5mme6WbaF_oSFaqT_nM",
  authDomain: "automarket-30e57.firebaseapp.com",
  projectId: "automarket-30e57",
  storageBucket: "automarket-30e57.firebasestorage.app",
  messagingSenderId: "223212735638",
  appId: "1:223212735638:web:fa179d908fd4f5442e86b7"
};

const app  = initializeApp(firebaseConfig);
const auth = getAuth(app);

async function saveUser(email, nom, prenom, uid) {
  const fd = new FormData();
  fd.append('action', 'social');
  fd.append('email',  email);
  fd.append('nom',    nom);
  fd.append('prenom', prenom);
  fd.append('uid',    uid);

  const res  = await fetch('inscription.php', { method:'POST', body:fd });
  const json = await res.json();

  if (json.success) window.location.href = 'index.php';
  else alert('Erreur : ' + json.message);
}

window.loginGoogle = async function() {
  try {
    const result = await signInWithPopup(auth, new GoogleAuthProvider());
    const user   = result.user;
    const parts  = (user.displayName || '').split(' ');
    await saveUser(
      user.email,
      parts.slice(1).join(' '),
      parts[0] || '',
      user.uid
    );
  } catch(e) {
    alert('Erreur Google : ' + e.message);
  }
};

window.loginFacebook = async function() {
  try {
    const result = await signInWithPopup(auth, new FacebookAuthProvider());
    const user   = result.user;
    const parts  = (user.displayName || '').split(' ');
    await saveUser(
      user.email || '',
      parts.slice(1).join(' '),
      parts[0] || '',
      user.uid
    );
  } catch(e) {
    alert('Erreur Facebook : ' + e.message);
  }
};