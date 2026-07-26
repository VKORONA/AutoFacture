package main

import (
	"bufio"
	"fmt"
	"io"
	"net/http"
	"os"
	"os/exec"
	"path/filepath"
	"runtime"
	"strings"
	"time"
)

const appURL = "http://localhost"

func main() {
	exe, err := os.Executable()
	if err != nil {
		fatal("Impossible de déterminer le dossier de l'application", err)
	}
	dir := filepath.Dir(exe)
	logFile, _ := os.OpenFile(filepath.Join(dir, "AutoFacture-lancement.log"), os.O_CREATE|os.O_WRONLY|os.O_APPEND, 0644)
	if logFile != nil {
		defer logFile.Close()
	}
	out := io.MultiWriter(os.Stdout, logFile)

	fmt.Fprintln(out, "AutoFacture — démarrage de la dernière version GitHub")
	fmt.Fprintln(out, "Dossier :", dir)

	if _, err := exec.LookPath("docker"); err != nil {
		message("Docker Desktop est nécessaire. Installez ou démarrez Docker Desktop, puis relancez AutoFacture.exe.")
		pause()
		return
	}

	if !dockerReady(dir) {
		fmt.Fprintln(out, "Docker Desktop n'est pas encore disponible. Tentative de démarrage...")
		startDockerDesktop()
		deadline := time.Now().Add(3 * time.Minute)
		for time.Now().Before(deadline) {
			if dockerReady(dir) {
				break
			}
			time.Sleep(3 * time.Second)
		}
	}
	if !dockerReady(dir) {
		message("Docker Desktop ne répond pas. Ouvrez Docker Desktop, attendez qu'il soit prêt, puis relancez AutoFacture.exe.")
		pause()
		return
	}

	envPath := filepath.Join(dir, ".env")
	if _, err := os.Stat(envPath); os.IsNotExist(err) {
		if err := copyFile(filepath.Join(dir, ".env.example"), envPath); err != nil {
			fatal("Création du fichier .env impossible", err)
		}
	}

	marker := filepath.Join(dir, ".autofacture_initialized")
	_, markerErr := os.Stat(marker)
	firstLaunch := os.IsNotExist(markerErr)

	fmt.Fprintln(out, "Démarrage des services AutoFacture...")
	if err := compose(out, dir, "up", "-d", "--build"); err != nil {
		message("Le démarrage Docker a échoué. Consultez AutoFacture-lancement.log.")
		pause()
		return
	}

	if firstLaunch {
		fmt.Fprintln(out, "Initialisation de la base et du compte local...")
		steps := [][]string{
			{"exec", "-T", "app", "composer", "install", "--no-interaction", "--prefer-dist", "--optimize-autoloader"},
			{"exec", "-T", "app", "php", "artisan", "key:generate", "--force"},
			{"exec", "-T", "app", "php", "artisan", "migrate", "--seed", "--force"},
			{"exec", "-T", "app", "php", "artisan", "autofacture:bootstrap-local"},
		}
		for _, step := range steps {
			if err := compose(out, dir, step...); err != nil {
				message("L'initialisation AutoFacture a échoué. Consultez AutoFacture-lancement.log.")
				pause()
				return
			}
		}
		_ = os.WriteFile(marker, []byte(time.Now().Format(time.RFC3339)), 0644)
	} else {
		_ = compose(out, dir, "exec", "-T", "app", "php", "artisan", "migrate", "--force")
	}

	fmt.Fprintln(out, "Vérification de l'application...")
	deadline := time.Now().Add(2 * time.Minute)
	for time.Now().Before(deadline) {
		if httpReady() {
			break
		}
		time.Sleep(2 * time.Second)
	}
	if !httpReady() {
		message("AutoFacture a démarré mais la page locale ne répond pas encore. Consultez AutoFacture-lancement.log et Docker Desktop.")
		pause()
		return
	}

	fmt.Fprintln(out, "AutoFacture est prêt :", appURL)
	fmt.Fprintln(out, "Identifiant : admin")
	fmt.Fprintln(out, "Mot de passe : autofacture-dev")
	openBrowser(appURL)
}

func compose(out io.Writer, dir string, args ...string) error {
	cmdArgs := append([]string{"compose"}, args...)
	cmd := exec.Command("docker", cmdArgs...)
	cmd.Dir = dir
	cmd.Stdout = out
	cmd.Stderr = out
	cmd.Stdin = os.Stdin
	return cmd.Run()
}

func dockerReady(dir string) bool {
	cmd := exec.Command("docker", "info")
	cmd.Dir = dir
	cmd.Stdout = io.Discard
	cmd.Stderr = io.Discard
	return cmd.Run() == nil
}

func startDockerDesktop() {
	candidates := []string{
		filepath.Join(os.Getenv("ProgramFiles"), "Docker", "Docker", "Docker Desktop.exe"),
		filepath.Join(os.Getenv("ProgramW6432"), "Docker", "Docker", "Docker Desktop.exe"),
		filepath.Join(os.Getenv("LOCALAPPDATA"), "Docker", "Docker Desktop.exe"),
	}
	for _, path := range candidates {
		if path == "" {
			continue
		}
		if _, err := os.Stat(path); err == nil {
			_ = exec.Command(path).Start()
			return
		}
	}
}

func httpReady() bool {
	client := http.Client{Timeout: 3 * time.Second}
	resp, err := client.Get(appURL)
	if err != nil {
		return false
	}
	defer resp.Body.Close()
	return resp.StatusCode >= 200 && resp.StatusCode < 500
}

func openBrowser(url string) {
	if runtime.GOOS == "windows" {
		_ = exec.Command("rundll32", "url.dll,FileProtocolHandler", url).Start()
	}
}

func copyFile(src, dst string) error {
	in, err := os.Open(src)
	if err != nil {
		return err
	}
	defer in.Close()
	out, err := os.Create(dst)
	if err != nil {
		return err
	}
	defer out.Close()
	_, err = io.Copy(out, in)
	return err
}

func message(text string) {
	escaped := strings.ReplaceAll(text, "'", "''")
	script := "Add-Type -AssemblyName PresentationFramework; [System.Windows.MessageBox]::Show('" + escaped + "','AutoFacture')"
	_ = exec.Command("powershell", "-NoProfile", "-Command", script).Run()
	fmt.Println(text)
}

func fatal(text string, err error) {
	message(fmt.Sprintf("%s : %v", text, err))
	pause()
	os.Exit(1)
}

func pause() {
	fmt.Print("Appuyez sur Entrée pour fermer...")
	_, _ = bufio.NewReader(os.Stdin).ReadString('\n')
}
