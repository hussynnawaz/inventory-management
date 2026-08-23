using Microsoft.Web.WebView2.WinForms;
using System.Diagnostics;

internal static class Program
{
    [STAThread]
    static void Main()
    {
        ApplicationConfiguration.Initialize();

        string root = AppContext.BaseDirectory;
        string php = Path.Combine(root, "php", "php.exe");
        string app = Path.Combine(root, "app");
        string ini = Path.Combine(root, "php", "php.ini");

        var server = Process.Start(new ProcessStartInfo
        {
            FileName = php,
            Arguments = $"-c \"{ini}\" -S 127.0.0.1:8000 -t \"{app}\"",
            WorkingDirectory = app,
            UseShellExecute = false,
            CreateNoWindow = true
        });

        var form = new Form
        {
            Text = "POS",
            WindowState = FormWindowState.Maximized
        };

        var web = new WebView2
        {
            Dock = DockStyle.Fill
        };

        form.Controls.Add(web);

        form.Load += async (_, _) =>
        {
            try
            {
                await web.EnsureCoreWebView2Async();
                web.Source = new Uri("http://127.0.0.1:8000");
            }
            catch (Exception ex)
            {
                MessageBox.Show(ex.ToString(), "WebView2 Error");
            }
        };

        form.FormClosed += (_, _) =>
        {
            try { server?.Kill(true); } catch { }
        };

        Application.Run(form);
    }
}