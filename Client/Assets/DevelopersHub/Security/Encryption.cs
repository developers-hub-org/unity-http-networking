namespace DevelopersHub
{

    using UnityEngine;
    using System.Security.Cryptography;
    using System.IO;
    using System.Text;
    using System;
    using System.Linq;

    public class Encryption : MonoBehaviour
    {

        private static string charset = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!#%^&*()_+-=";

        public static string EncryptAES(string text, string iv, string password)
        {
            return EncryptString(text, PasswordToByteArray(password), Encoding.ASCII.GetBytes(iv));
        }

        public static string DecryptAES(string text, string iv, string password)
        {
            return DecryptString(text, PasswordToByteArray(password), Encoding.ASCII.GetBytes(iv));
        }

        private static byte[] PasswordToByteArray(string password)
        {
            SHA256 mySHA256 = SHA256Managed.Create();
            return mySHA256.ComputeHash(Encoding.ASCII.GetBytes(password));
        }

        public static string EncryptString(string text, byte[] key, byte[] iv)
        {
            Aes encryptor = Aes.Create();
            encryptor.Mode = CipherMode.CBC;
            encryptor.Key = key.Take(32).ToArray();
            encryptor.IV = iv;
            MemoryStream memoryStream = new MemoryStream();
            ICryptoTransform aesEncryptor = encryptor.CreateEncryptor();
            CryptoStream cryptoStream = new CryptoStream(memoryStream, aesEncryptor, CryptoStreamMode.Write);
            byte[] plainBytes = Encoding.ASCII.GetBytes(text);
            cryptoStream.Write(plainBytes, 0, plainBytes.Length);
            cryptoStream.FlushFinalBlock();
            byte[] cipherBytes = memoryStream.ToArray();
            memoryStream.Close();
            cryptoStream.Close();
            string cipherText = Convert.ToBase64String(cipherBytes, 0, cipherBytes.Length);
            return cipherText;
        }

        public static string DecryptString(string text, byte[] key, byte[] iv)
        {
            Aes encryptor = Aes.Create();
            encryptor.Mode = CipherMode.CBC;
            encryptor.Key = key.Take(32).ToArray();
            encryptor.IV = iv;
            MemoryStream memoryStream = new MemoryStream();
            ICryptoTransform aesDecryptor = encryptor.CreateDecryptor();
            CryptoStream cryptoStream = new CryptoStream(memoryStream, aesDecryptor, CryptoStreamMode.Write);
            string plainText = String.Empty;
            try
            {
                byte[] cipherBytes = Convert.FromBase64String(text);
                cryptoStream.Write(cipherBytes, 0, cipherBytes.Length);
                cryptoStream.FlushFinalBlock();
                byte[] plainBytes = memoryStream.ToArray();
                plainText = Encoding.ASCII.GetString(plainBytes, 0, plainBytes.Length);
            }
            finally
            {
                memoryStream.Close();
                cryptoStream.Close();
            }
            return plainText;
        }

        public static string GenerateVI(int lenght)
        {
            string iv = "";
            for (int i = 0; i < lenght; i++)
            {
                int r = UnityEngine.Random.Range(0, charset.Length);
                iv = iv + charset.Substring(r, 1);
            }
            return iv;
        }

        public static string EncryptMD5(string text, string key = "")
        {
            if (!string.IsNullOrEmpty(key))
            {
                text += key;
            }
            UTF8Encoding ue = new UTF8Encoding();
            byte[] bytes = ue.GetBytes(text);
            MD5CryptoServiceProvider md5 = new MD5CryptoServiceProvider();
            byte[] hashBytes = md5.ComputeHash(bytes);
            string hashString = "";
            for (int i = 0; i < hashBytes.Length; i++)
            {
                hashString = hashString + Convert.ToString(hashBytes[i], 16).PadLeft(2, '0');
            }
            return hashString.PadLeft(32, '0');
        }

    }
}