function checkLogin() {
    const user = localStorage.getItem('userId');
    return !(user!==null);
}