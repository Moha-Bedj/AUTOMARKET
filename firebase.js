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

async function saveUser(email, nom, prenom, uid, photo) {
  const fd = new FormData();
  fd.append('action', 'social');
  fd.append('email',  email);
  fd.append('nom',    nom);
  fd.append('prenom', prenom);
  fd.append('uid',    uid);
  fd.append('photo',  photo);

  const res  = await fetch('inscription.php', { method:'POST', body:fd });
  const json = await res.json();

  if (json.success) window.location.href = 'index.php';
  else alert('Erreur : ' + json.message);
}

let authInProgress = false;

window.loginGoogle = async function() {
  if (authInProgress) return;
  authInProgress = true;

  try {
    const result = await signInWithPopup(auth, new GoogleAuthProvider());
    const user   = result.user;
    let photo = user.photoURL || '';
    if (user.providerData && user.providerData[0]) {
      photo = user.providerData[0].photoURL || photo;
    }

    /* Demander une meilleure résolution (remplacer s96-c par s400-c) */
    if (photo.includes('=s96-c')) {
      photo = photo.replace('=s96-c', '=s400-c');
    }
    const parts  = (user.displayName || '').split(' ');
    await saveUser(
      user.email,
      parts.slice(1).join(' '),
      parts[0] || '',
      user.uid,
      user.photo
    );
  } catch(e) {
    if (e.code !== 'auth/cancelled-popup-request') {
      alert('Erreur Google : ' + e.message);
    }
  } finally {
    authInProgress = false;
  }
};

window.loginFacebook = async function() {
  if (authInProgress) return;
  authInProgress = true;

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
    if (e.code !== 'auth/cancelled-popup-request') {
      alert('Erreur Facebook : ' + e.message);
    }
  } finally {
    authInProgress = false;
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