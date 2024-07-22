import torch
from transformers import AutoTokenizer, AutoModelForSequenceClassification
from optimum.onnxruntime import ORTModelForSequenceClassification

model_name = 'philomath-1209/programming-language-identification'

tokenizer = AutoTokenizer.from_pretrained(model_name, subfolder="onnx")
model = ORTModelForSequenceClassification.from_pretrained(model_name, export=False, subfolder="onnx")

device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')

from sys import stdin

text = ""

for line in stdin:
  text += line + "\n"

inputs = tokenizer(text, return_tensors="pt",truncation=True)
with torch.no_grad():
  logits = model(**inputs).logits

predicted_class_id = logits.argmax().item()

print(model.config.id2label[predicted_class_id])
