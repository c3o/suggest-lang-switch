function suggestLangSwitchCheck() {

	// prereqs: variable is set from php; browser has preferred languages
	if (!suggestLangSwitch || !navigator.languages) return;
	
	// has user dismissed suggestion?
	if (document.cookie.indexOf('suggestlangswitch-dismiss') > -1) return; 

	var userLangsLong = navigator.languages; //["en-US", "zh-CN", "ja-JP"]
	var preferredLangs = [];

	userLangsLong.forEach(function(langLong) {
		var lang = langLong.substring(0, 2);
		if (suggestLangSwitch.translations[lang] && !preferredLangs.includes(lang)) {
			preferredLangs.push(lang);
		}
	});

	if (preferredLangs[0] && suggestLangSwitch.current != preferredLangs[0]) { 
		suggestLangSwitchShow(preferredLangs[0]);
	}

}

function suggestLangSwitchShow(lang) {
	var el = document.getElementById('suggest-lang-switch');
	el.firstChild.href = suggestLangSwitch.translations[lang].url;
	el.firstChild.innerHTML = suggestLangSwitch.translations[lang].prompt;

	el.style.display = 'block';
	setTimeout(function() {
		el.style.maxHeight = '100px'; // anim
	}, 20);
}

function suggestLangSwitchDismiss(el) {
	el.style.maxHeight = 0; // anim
	document.cookie = "suggestlangswitch-dismiss=1; path=/";
	return false;
}

window.addEventListener('DOMContentLoaded', (event) => {
	suggestLangSwitchCheck();
});